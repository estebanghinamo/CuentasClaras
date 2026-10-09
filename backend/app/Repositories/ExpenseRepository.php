<?php

namespace App\Repositories;

use App\DTOs\Finance\ExpenseFiltersDto;
use App\Exceptions\StoredProcedureException;

class ExpenseRepository extends BaseRepository
{
    public function list(int $workspaceId, ExpenseFiltersDto $filters): array
    {
        return $this->call('sp_expense_list', $filters->toPayload($workspaceId));
    }

    public function find(int $workspaceId, int $expenseId): ?array
    {
        try {
            return $this->call('sp_expense_get', ['workspace_id' => $workspaceId, 'expense_id' => $expenseId]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function create(
        int $workspaceId,
        int $userId,
        ?int $categoryId,
        string $amount,
        ?string $description,
        string $paymentMethod,
        string $date,
        ?int $paidByUserId = null,
    ): array {
        return $this->call('sp_expense_create', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'payment_method' => $paymentMethod,
            'date' => $date,
            'paid_by_user_id' => $paidByUserId,
        ]);
    }

    public function update(
        int $workspaceId,
        int $expenseId,
        ?int $categoryId,
        string $amount,
        ?string $description,
        string $paymentMethod,
        string $date,
        ?int $paidByUserId = null,
    ): array {
        return $this->call('sp_expense_update', [
            'workspace_id' => $workspaceId,
            'expense_id' => $expenseId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'payment_method' => $paymentMethod,
            'date' => $date,
            'paid_by_user_id' => $paidByUserId,
        ]);
    }

    public function delete(int $workspaceId, int $expenseId): void
    {
        $this->call('sp_expense_delete', ['workspace_id' => $workspaceId, 'expense_id' => $expenseId]);
    }

    public function paymentMethodsUsed(int $workspaceId): array
    {
        return $this->call('sp_expense_payment_methods', ['workspace_id' => $workspaceId]);
    }
}
