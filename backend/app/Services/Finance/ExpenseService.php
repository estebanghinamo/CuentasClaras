<?php

namespace App\Services\Finance;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Finance\ExpenseDto;
use App\DTOs\Finance\ExpenseFiltersDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use App\Repositories\ExpenseRepository;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSummaries;
use App\Services\Closing\ClosingGuard;
use App\Services\Support\SpErrorMapper;
use App\Services\Workspace\WorkspacePermissions;
use App\Support\Period;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly ClosingGuard $closingGuard,
        private readonly WorkspacePermissions $permissions,
        private readonly AuditService $audit,
        private readonly BudgetAlertService $budgetAlerts,
    ) {}

    public function list(WorkspaceMembershipDto $membership, ExpenseFiltersDto $filters): array
    {
        $result = $this->expenses->list($membership->workspaceId, $filters);

        $items = array_map(
            fn (array $row) => ExpenseDto::fromArray(
                $row,
                $this->permissions->canEditPersonalRecord($membership, (int) $row['user_id']),
            ),
            $result['items'],
        );

        return [
            'items' => $items,
            'total' => (int) $result['total'],
            'total_amount' => (float) $result['total_amount'],
        ];
    }

    public function get(WorkspaceMembershipDto $membership, int $expenseId): ExpenseDto
    {
        $row = $this->expenses->find($membership->workspaceId, $expenseId);

        if ($row === null) {
            throw new NotFoundException();
        }

        return ExpenseDto::fromArray(
            $row,
            $this->permissions->canEditPersonalRecord($membership, (int) $row['user_id']),
        );
    }

    public function create(WorkspaceMembershipDto $membership, int $userId, array $data): ExpenseDto
    {
        $period = Period::fromDate($data['date']);
        $this->closingGuard->assertPeriodNotTooFar($period);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        try {
            $row = $this->expenses->create(
                $membership->workspaceId,
                $userId,
                $data['category_id'] ?? null,
                (string) $data['amount'],
                $data['description'] ?? null,
                $data['payment_method'],
                $data['date'],
                $data['paid_by_user_id'] ?? null,
            );
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        $dto = ExpenseDto::fromArray($row, true);

        $this->budgetAlerts->evaluate($membership->workspaceId, $dto->categoryId, $period);

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $userId,
            entityType: 'expense',
            entityId: $dto->id,
            action: 'created',
            summary: AuditSummaries::for('expense', 'created', [
                'amount' => $dto->amount,
                'category' => $dto->categoryName,
            ]),
            oldValue: null,
            newValue: $this->auditableFields($dto),
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        return $dto;
    }

    public function update(WorkspaceMembershipDto $membership, int $expenseId, array $data): ExpenseDto
    {
        $existing = $this->expenses->find($membership->workspaceId, $expenseId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        $this->permissions->assertCanEditPersonalRecord($membership, (int) $existing['user_id']);

        $period = Period::fromDate($data['date']);
        $this->closingGuard->assertPeriodNotTooFar($period);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        try {
            $row = $this->expenses->update(
                $membership->workspaceId,
                $expenseId,
                $data['category_id'] ?? null,
                (string) $data['amount'],
                $data['description'] ?? null,
                $data['payment_method'],
                $data['date'],
                $data['paid_by_user_id'] ?? null,
            );
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        $dto = ExpenseDto::fromArray(
            $row,
            $this->permissions->canEditPersonalRecord($membership, (int) $row['user_id']),
        );
        $oldDto = ExpenseDto::fromArray($existing, true);

        $oldPeriod = Period::fromDate($oldDto->date);
        $this->budgetAlerts->evaluate($membership->workspaceId, $dto->categoryId, $period);
        if ($oldDto->categoryId !== $dto->categoryId || !$oldPeriod->equals($period)) {
            $this->budgetAlerts->evaluate($membership->workspaceId, $oldDto->categoryId, $oldPeriod);
        }

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'expense',
            entityId: $dto->id,
            action: 'updated',
            summary: AuditSummaries::for('expense', 'updated'),
            oldValue: $this->auditableFields($oldDto),
            newValue: $this->auditableFields($dto),
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        return $dto;
    }

    public function delete(WorkspaceMembershipDto $membership, int $expenseId): void
    {
        $existing = $this->expenses->find($membership->workspaceId, $expenseId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        $this->permissions->assertCanEditPersonalRecord($membership, (int) $existing['user_id']);

        $this->expenses->delete($membership->workspaceId, $expenseId);

        $oldDto = ExpenseDto::fromArray($existing, true);

        $this->budgetAlerts->evaluate($membership->workspaceId, $oldDto->categoryId, Period::fromDate($oldDto->date));

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'expense',
            entityId: $expenseId,
            action: 'deleted',
            summary: AuditSummaries::for('expense', 'deleted', ['amount' => $oldDto->amount]),
            oldValue: $this->auditableFields($oldDto),
            newValue: null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));
    }

    /** @return string[] */
    public function paymentMethodsUsed(int $workspaceId): array
    {
        return $this->expenses->paymentMethodsUsed($workspaceId);
    }

    private function auditableFields(ExpenseDto $dto): array
    {
        return [
            'category_id' => $dto->categoryId,
            'amount' => $dto->amount,
            'description' => $dto->description,
            'payment_method' => $dto->paymentMethod,
            'date' => $dto->date,
            'paid_by_user_id' => $dto->paidByUserId,
        ];
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
