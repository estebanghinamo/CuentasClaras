<?php

namespace App\Services\Closing;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Closing\MonthlyClosingDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidStateException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use App\Jobs\ScanSmartSuggestionsJob;
use App\Repositories\InstallmentRepository;
use App\Repositories\MonthlyClosingRepository;
use App\Repositories\ServicePaymentRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSummaries;
use App\Services\Notifications\NotificationService;
use App\Services\Reports\HealthScoreService;
use App\Services\Support\SpErrorMapper;
use App\Support\Money;
use App\Support\Period;
use Illuminate\Support\Facades\Log;

/**
 * Cierre mensual congelado (ESPECIFICACION_TECNICA.md M-13). `close()` es el
 * cuerpo de CloseMonthJob - separado del Job para poder testear sin cola.
 */
class MonthlyClosingService
{
    public function __construct(
        private readonly MonthlyClosingRepository $closings,
        private readonly HealthScoreService $healthScores,
        private readonly ServicePaymentRepository $servicePayments,
        private readonly InstallmentRepository $installments,
        private readonly WorkspaceRepository $workspaces,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    /** Idempotente: si ya existe un cierre para el período, no hace nada. */
    public function close(int $workspaceId, Period $period): void
    {
        if ($this->closings->exists($workspaceId, $period)) {
            Log::info('Cierre ya existe, omitido (idempotente)', [
                'workspace_id' => $workspaceId,
                'period' => $period->label(),
            ]);

            return;
        }

        $this->servicePayments->markOverdue($workspaceId, $period->lastDay());
        $this->installments->settlePeriod($workspaceId, $period->year, $period->month);

        $computed = $this->closings->compute($workspaceId, $period);
        $remaining = Money::sub(
            Money::sub(
                Money::sub((string) $computed['total_income'], (string) $computed['total_expenses']),
                (string) $computed['total_services'],
            ),
            (string) $computed['total_installments'],
        );

        try {
            $row = $this->closings->create($workspaceId, $period, $computed, $remaining, 'system');
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'DUPLICATE') {
                Log::info('Carrera de cierre detectada, otro proceso ya cerró este período', [
                    'workspace_id' => $workspaceId,
                    'period' => $period->label(),
                ]);

                return;
            }

            throw $e;
        }

        $this->healthScores->computeAndStore($workspaceId, $period);

        $members = $this->workspaces->listMembers($workspaceId);
        $owner = collect($members)->firstWhere('role', 'owner');

        if ($owner !== null) {
            $this->audit->log(new AuditEntryDto(
                workspaceId: $workspaceId,
                userId: (int) $owner['user_id'],
                entityType: 'monthly_closing',
                entityId: (int) $row['id'],
                action: 'created',
                summary: AuditSummaries::for('monthly_closing', 'created', ['period' => $period->label()]),
            ));
        }

        foreach ($members as $member) {
            $this->notifications->notify(
                userId: (int) $member['user_id'],
                type: 'month_closed',
                title: 'Mes cerrado',
                body: $remaining !== '' && Money::cmp($remaining, '0') > 0
                    ? "Se cerró {$period->label()} con \${$remaining} de sobrante."
                    : "Se cerró {$period->label()}.",
                route: "/w/{$workspaceId}/closings/{$period->year}/{$period->month}",
                workspaceId: $workspaceId,
                payload: [
                    'year' => $period->year,
                    'month' => $period->month,
                    'remaining_amount' => Money::toFloat($remaining),
                ],
            );
        }

        ScanSmartSuggestionsJob::dispatch($workspaceId);
    }

    /** @return MonthlyClosingDto[] sin breakdown */
    public function list(int $workspaceId): array
    {
        return array_map(
            fn (array $row) => MonthlyClosingDto::fromArray($row),
            $this->closings->list($workspaceId),
        );
    }

    public function get(int $workspaceId, Period $period): MonthlyClosingDto
    {
        $row = $this->closings->get($workspaceId, $period);

        if ($row === null) {
            throw new NotFoundException('Ese mes todavía no está cerrado.');
        }

        return MonthlyClosingDto::fromArray($row);
    }

    /** @param array{to_wallet: string, to_goals: array{goal_id:int,amount:string}[], to_next_month: string} $data */
    public function allocate(WorkspaceMembershipDto $membership, Period $period, array $data): MonthlyClosingDto
    {
        $closing = $this->get($membership->workspaceId, $period);

        if ($closing->allocationStatus === 'not_applicable') {
            throw new InvalidStateException('Este mes no tuvo sobrante.');
        }

        $toNextMonth = (string) $data['to_next_month'];

        if (Money::cmp($toNextMonth, '0') > 0 && !$closing->nextMonthOpen) {
            throw new InvalidStateException(
                'El mes siguiente ya está cerrado; no se puede asignar el sobrante como ingreso ahí.',
            );
        }

        $goalsSum = array_reduce(
            $data['to_goals'],
            fn (string $carry, array $goal) => Money::add($carry, (string) $goal['amount']),
            '0',
        );
        $requested = Money::add(Money::add((string) $data['to_wallet'], $goalsSum), $toNextMonth);

        if (Money::cmp($requested, $closing->unallocatedAmount()) > 0) {
            throw new InsufficientFundsException(
                "El monto supera el sobrante sin asignar (\${$closing->unallocatedAmount()}).",
            );
        }

        try {
            $row = $this->closings->allocate(
                $membership->workspaceId,
                $closing->id,
                $membership->userId,
                (string) $data['to_wallet'],
                $data['to_goals'],
                $toNextMonth,
                $period,
            );
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return MonthlyClosingDto::fromArray($row);
    }
}
