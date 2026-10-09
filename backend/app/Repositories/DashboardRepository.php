<?php

namespace App\Repositories;

use App\Support\Period;

class DashboardRepository extends BaseRepository
{
    public function get(int $workspaceId, Period $period): array
    {
        return $this->call('sp_dashboard_get', [
            'workspace_id' => $workspaceId,
            'year' => $period->year,
            'month' => $period->month,
        ]);
    }

    /** @return array{expenses_to_date: float} */
    public function dailySpend(int $workspaceId, Period $period, string $today): array
    {
        return $this->call('sp_dashboard_daily_spend', [
            'workspace_id' => $workspaceId,
            'year' => $period->year,
            'month' => $period->month,
            'today' => $today,
        ]);
    }
}
