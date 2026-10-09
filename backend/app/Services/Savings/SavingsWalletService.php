<?php

namespace App\Services\Savings;

use App\DTOs\Savings\SavingsMovementDto;
use App\DTOs\Savings\SavingsWalletDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\StoredProcedureException;
use App\Repositories\SavingsWalletRepository;
use App\Services\Audit\AuditSummaries;
use App\Services\Closing\ClosingGuard;
use App\Services\Reports\DashboardService;
use App\Services\Support\SpErrorMapper;
use App\Support\Period;
use Carbon\CarbonImmutable;

/**
 * El monedero es una entidad COMPARTIDA del workspace (no un "registro
 * personal" con dueño): cualquier miembro deposita/retira sin chequeo de
 * WorkspacePermissions::canEditPersonalRecord, sea cual sea el tipo de
 * workspace - ver PROJECT_STATE.json.
 */
class SavingsWalletService
{
    public function __construct(
        private readonly SavingsWalletRepository $wallet,
        private readonly ClosingGuard $closingGuard,
        private readonly DashboardService $dashboard,
    ) {}

    public function get(WorkspaceMembershipDto $membership): SavingsWalletDto
    {
        return SavingsWalletDto::fromArray($this->wallet->get($membership->workspaceId));
    }

    /**
     * Un depósito manual resta del "Disponible" del mes (ver DashboardService)
     * - no puede depositar más de lo que todavía no tiene asignado, a pedido
     * explícito del usuario (2026-09-21).
     */
    public function deposit(
        WorkspaceMembershipDto $membership,
        float $amount,
        ?string $note,
        int $userId,
    ): SavingsMovementDto {
        $available = $this->dashboard->availableForPeriod($membership, Period::current());

        if ($amount > $available) {
            throw new InsufficientFundsException('No podés depositar más de tu disponible del mes.');
        }

        return $this->createMovement($membership, 'deposit', $amount, $note, $userId);
    }

    public function withdraw(
        WorkspaceMembershipDto $membership,
        float $amount,
        ?string $note,
        int $userId,
    ): SavingsMovementDto {
        return $this->createMovement($membership, 'withdraw', $amount, $note, $userId);
    }

    /** @return array{items: SavingsMovementDto[], total: int} */
    public function listMovements(
        WorkspaceMembershipDto $membership,
        int $page,
        int $perPage,
        ?string $type,
    ): array {
        $result = $this->wallet->listMovements($membership->workspaceId, $page, $perPage, $type);

        return [
            'items' => array_map(fn (array $row) => SavingsMovementDto::fromArray($row), $result['items']),
            'total' => (int) $result['total'],
        ];
    }

    /**
     * Saldo al cierre de cada uno de los últimos $months meses (walk-forward:
     * si un mes no tuvo movimientos, arrastra el saldo del último que sí tuvo,
     * o 0 si todavía no hubo ninguno). Devuelve siempre $months puntos.
     *
     * @return array{year:int,month:int,balance_end:float}[]
     */
    public function history(WorkspaceMembershipDto $membership, int $months): array
    {
        $movements = $this->wallet->history($membership->workspaceId);
        $today = CarbonImmutable::now(config('app.timezone'));

        $periods = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $today->subMonthsNoOverflow($i);
            $periods[] = Period::fromYearMonth((int) $date->year, (int) $date->month);
        }

        $index = 0;
        $count = count($movements);
        $runningBalance = 0.0;
        $result = [];

        foreach ($periods as $period) {
            while ($index < $count && $this->isOnOrBefore($movements[$index], $period)) {
                $runningBalance = (float) $movements[$index]['balance_after'];
                $index++;
            }

            $result[] = ['year' => $period->year, 'month' => $period->month, 'balance_end' => $runningBalance];
        }

        return $result;
    }

    private function isOnOrBefore(array $movement, Period $period): bool
    {
        $movementPeriod = Period::fromYearMonth((int) $movement['year'], (int) $movement['month']);

        return !$movementPeriod->isAfter($period);
    }

    private function createMovement(
        WorkspaceMembershipDto $membership,
        string $type,
        float $amount,
        ?string $note,
        int $userId,
    ): SavingsMovementDto {
        $period = Period::current();
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        $audit = [
            'user_id' => $userId,
            'summary' => AuditSummaries::for('savings_movement', 'created', [
                'type' => $type,
                'amount' => $amount,
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent() !== null ? substr(request()->userAgent(), 0, 255) : null,
        ];

        try {
            $row = $this->wallet->createMovement($membership->workspaceId, [
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'year' => $period->year,
                'month' => $period->month,
                'note' => $note,
                'source' => 'manual',
                'closing_id' => null,
            ], $audit);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return SavingsMovementDto::fromArray($row);
    }
}
