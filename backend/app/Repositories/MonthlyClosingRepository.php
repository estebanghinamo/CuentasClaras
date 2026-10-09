<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;
use App\Support\Period;

class MonthlyClosingRepository extends BaseRepository
{
    public function exists(int $workspaceId, Period $period): bool
    {
        $result = $this->call('sp_monthly_closing_exists', [
            'workspace_id' => $workspaceId,
            'year' => $period->year,
            'month' => $period->month,
        ]);

        return !empty($result['exists']);
    }

    /** Solo lectura - compartido con el dashboard (M-14). */
    public function compute(int $workspaceId, Period $period): array
    {
        return $this->call('sp_monthly_closing_compute', [
            'workspace_id' => $workspaceId,
            'year' => $period->year,
            'month' => $period->month,
        ]);
    }

    public function create(
        int $workspaceId,
        Period $period,
        array $computed,
        string $remainingAmount,
        string $closedBy,
    ): array {
        return $this->call('sp_monthly_closing_create', [
            'workspace_id' => $workspaceId,
            'year' => $period->year,
            'month' => $period->month,
            'total_income' => $computed['total_income'],
            'total_expenses' => $computed['total_expenses'],
            'total_services' => $computed['total_services'],
            'total_installments' => $computed['total_installments'],
            'remaining_amount' => $remainingAmount,
            'breakdown_json' => $computed['breakdown'],
            'closed_by' => $closedBy,
        ]);
    }

    public function get(int $workspaceId, Period $period): ?array
    {
        try {
            return $this->call('sp_monthly_closing_get', [
                'workspace_id' => $workspaceId,
                'year' => $period->year,
                'month' => $period->month,
            ]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }

            throw $e;
        }
    }

    public function list(int $workspaceId): array
    {
        return $this->call('sp_monthly_closing_list', ['workspace_id' => $workspaceId]);
    }

    public function allocate(
        int $workspaceId,
        int $closingId,
        int $userId,
        string $toWallet,
        array $toGoals,
        string $toNextMonth,
        Period $period,
    ): array {
        return $this->call('sp_monthly_closing_allocate', [
            'workspace_id' => $workspaceId,
            'closing_id' => $closingId,
            'user_id' => $userId,
            'to_wallet' => $toWallet,
            'to_goals' => $toGoals,
            'to_next_month' => $toNextMonth,
            'year' => $period->year,
            'month' => $period->month,
        ]);
    }
}
