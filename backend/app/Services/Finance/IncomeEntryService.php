<?php

namespace App\Services\Finance;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Finance\IncomeEntryDto;
use App\DTOs\Finance\IncomeSummaryDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\InvalidStateException;
use App\Exceptions\NotFoundException;
use App\Repositories\IncomeEntryRepository;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSummaries;
use App\Services\Closing\ClosingGuard;
use App\Services\Workspace\WorkspacePermissions;
use App\Support\Period;
use Illuminate\Validation\ValidationException;

class IncomeEntryService
{
    public function __construct(
        private readonly IncomeEntryRepository $incomeEntries,
        private readonly ClosingGuard $closingGuard,
        private readonly WorkspacePermissions $permissions,
        private readonly AuditService $audit,
    ) {}

    public function list(WorkspaceMembershipDto $membership, int $year, int $month): IncomeSummaryDto
    {
        $result = $this->incomeEntries->list($membership->workspaceId, $year, $month);

        $entries = array_map(
            fn (array $row) => IncomeEntryDto::fromArray(
                $row,
                $this->permissions->canEditPersonalRecord($membership, (int) $row['user_id']),
            ),
            $result['entries'],
        );

        return new IncomeSummaryDto($entries, (float) $result['total'], $result['by_user']);
    }

    public function create(WorkspaceMembershipDto $membership, int $userId, array $data): IncomeEntryDto
    {
        $period = Period::fromDate($data['date']);
        $this->assertCurrentPeriod($period);
        $this->closingGuard->assertPeriodNotTooFar($period);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        $row = $this->incomeEntries->create(
            $membership->workspaceId,
            $userId,
            (string) $data['amount'],
            $data['concept'],
            $data['date'],
            $period->year,
            $period->month,
        );

        $dto = IncomeEntryDto::fromArray($row, true);

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $userId,
            entityType: 'income_entry',
            entityId: $dto->id,
            action: 'created',
            summary: AuditSummaries::for('income_entry', 'created', [
                'amount' => $dto->amount,
                'concept' => $dto->concept,
            ]),
            oldValue: null,
            newValue: ['amount' => $dto->amount, 'concept' => $dto->concept, 'date' => $dto->date],
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        return $dto;
    }

    public function update(WorkspaceMembershipDto $membership, int $incomeEntryId, array $data): IncomeEntryDto
    {
        $existing = $this->incomeEntries->find($membership->workspaceId, $incomeEntryId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        $this->permissions->assertCanEditPersonalRecord($membership, (int) $existing['user_id']);
        $this->assertNotClosingAllocation($existing);

        $period = Period::fromDate($data['date']);
        $this->assertCurrentPeriod($period);
        $this->closingGuard->assertPeriodNotTooFar($period);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        $row = $this->incomeEntries->update(
            $membership->workspaceId,
            $incomeEntryId,
            (string) $data['amount'],
            $data['concept'],
            $data['date'],
            $period->year,
            $period->month,
        );

        $dto = IncomeEntryDto::fromArray(
            $row,
            $this->permissions->canEditPersonalRecord($membership, (int) $row['user_id']),
        );

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'income_entry',
            entityId: $dto->id,
            action: 'updated',
            summary: AuditSummaries::for('income_entry', 'updated', ['concept' => $dto->concept]),
            oldValue: [
                'amount' => (float) $existing['amount'],
                'concept' => $existing['concept'],
                'date' => $existing['date'],
            ],
            newValue: ['amount' => $dto->amount, 'concept' => $dto->concept, 'date' => $dto->date],
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        return $dto;
    }

    public function delete(WorkspaceMembershipDto $membership, int $incomeEntryId): void
    {
        $existing = $this->incomeEntries->find($membership->workspaceId, $incomeEntryId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        $this->permissions->assertCanEditPersonalRecord($membership, (int) $existing['user_id']);
        $this->assertNotClosingAllocation($existing);
        $this->assertCurrentPeriod(Period::fromDate($existing['date']));

        $this->incomeEntries->delete($membership->workspaceId, $incomeEntryId);

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'income_entry',
            entityId: $incomeEntryId,
            action: 'deleted',
            summary: AuditSummaries::for('income_entry', 'deleted', [
                'amount' => (float) $existing['amount'],
                'concept' => $existing['concept'],
            ]),
            oldValue: [
                'amount' => (float) $existing['amount'],
                'concept' => $existing['concept'],
                'date' => $existing['date'],
            ],
            newValue: null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));
    }

    /** @param array<string,mixed> $entry */
    private function assertNotClosingAllocation(array $entry): void
    {
        if (($entry['source'] ?? 'manual') === 'closing') {
            throw new InvalidStateException(
                'Este ingreso es la asignación del sobrante de un cierre mensual, un monto fijo que no se puede '
                .'editar ni eliminar acá. Si te equivocaste, podés ir a Monedero o a Metas y aportar ese mismo '
                .'monto desde el Disponible.',
            );
        }
    }

    private function assertCurrentPeriod(Period $period): void
    {
        if (!$period->equals(Period::current())) {
            throw ValidationException::withMessages([
                'period' => ['Solo se pueden modificar ingresos del mes actual.'],
            ]);
        }
    }

    private function ip(): ?string
    {
        return request()->ip();
    }

    private function userAgent(): ?string
    {
        $agent = request()->userAgent();

        return $agent !== null ? substr($agent, 0, 255) : null;
    }
}
