<?php

namespace App\Repositories;

class BudgetRepository extends BaseRepository
{
    public function list(int $workspaceId, int $year, int $month): array
    {
        return $this->call('sp_budget_list', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function upsert(int $workspaceId, int $categoryId, int $year, int $month, string $limitAmount): array
    {
        return $this->call('sp_budget_upsert', [
            'workspace_id' => $workspaceId,
            'category_id' => $categoryId,
            'year' => $year,
            'month' => $month,
            'limit_amount' => $limitAmount,
        ]);
    }

    public function delete(int $workspaceId, int $budgetId): void
    {
        $this->call('sp_budget_delete', [
            'workspace_id' => $workspaceId,
            'budget_id' => $budgetId,
        ]);
    }

    public function copyFromPeriod(
        int $workspaceId,
        int $fromYear,
        int $fromMonth,
        int $toYear,
        int $toMonth,
        bool $overwrite,
    ): array {
        return $this->call('sp_budget_copy_from_period', [
            'workspace_id' => $workspaceId,
            'from_year' => $fromYear,
            'from_month' => $fromMonth,
            'to_year' => $toYear,
            'to_month' => $toMonth,
            'overwrite' => $overwrite,
        ]);
    }

    public function progress(int $workspaceId, int $categoryId, int $year, int $month): array
    {
        return $this->call('sp_budget_progress', [
            'workspace_id' => $workspaceId,
            'category_id' => $categoryId,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function updateAlertLevel(int $workspaceId, int $budgetId, string $alertLevel): void
    {
        $this->call('sp_budget_update_alert_level', [
            'workspace_id' => $workspaceId,
            'budget_id' => $budgetId,
            'alert_level' => $alertLevel,
        ]);
    }
}
