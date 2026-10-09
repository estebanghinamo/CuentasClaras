<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;
use App\Support\Period;

class HealthScoreRepository extends BaseRepository
{
    public function inputs(int $workspaceId, Period $period): array
    {
        return $this->call('sp_health_score_inputs', [
            'workspace_id' => $workspaceId,
            'year' => $period->year,
            'month' => $period->month,
        ]);
    }

    public function upsert(int $workspaceId, Period $period, int $score, array $breakdown): array
    {
        return $this->call('sp_health_score_upsert', [
            'workspace_id' => $workspaceId,
            'year' => $period->year,
            'month' => $period->month,
            'score' => $score,
            'breakdown_json' => $breakdown,
        ]);
    }

    public function get(int $workspaceId, Period $period): ?array
    {
        try {
            return $this->call('sp_health_score_get', [
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

    public function list(int $workspaceId, int $months): array
    {
        return $this->call('sp_health_score_list', ['workspace_id' => $workspaceId, 'months' => $months]);
    }
}
