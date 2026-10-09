<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class IncomeEntryRepository extends BaseRepository
{
    public function list(int $workspaceId, int $year, int $month): array
    {
        return $this->call('sp_income_entry_list', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function find(int $workspaceId, int $incomeEntryId): ?array
    {
        try {
            return $this->call('sp_income_entry_get', [
                'workspace_id' => $workspaceId,
                'income_entry_id' => $incomeEntryId,
            ]);
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
        string $amount,
        string $concept,
        string $date,
        int $year,
        int $month,
    ): array {
        return $this->call('sp_income_entry_create', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'amount' => $amount,
            'concept' => $concept,
            'date' => $date,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function update(
        int $workspaceId,
        int $incomeEntryId,
        string $amount,
        string $concept,
        string $date,
        int $year,
        int $month,
    ): array {
        return $this->call('sp_income_entry_update', [
            'workspace_id' => $workspaceId,
            'income_entry_id' => $incomeEntryId,
            'amount' => $amount,
            'concept' => $concept,
            'date' => $date,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function delete(int $workspaceId, int $incomeEntryId): void
    {
        $this->call('sp_income_entry_delete', ['workspace_id' => $workspaceId, 'income_entry_id' => $incomeEntryId]);
    }
}
