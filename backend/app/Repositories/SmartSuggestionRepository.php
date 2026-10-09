<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class SmartSuggestionRepository extends BaseRepository
{
    public function candidates(int $workspaceId, string $dateFrom, int $minOccurrences, float $tolerancePct): array
    {
        return $this->call('sp_smart_suggestion_candidates', [
            'workspace_id' => $workspaceId,
            'date_from' => $dateFrom,
            'min_occurrences' => $minOccurrences,
            'tolerance_pct' => $tolerancePct,
        ]);
    }

    public function upsert(int $workspaceId, array $candidate): array
    {
        return $this->call('sp_smart_suggestion_upsert', [
            'workspace_id' => $workspaceId,
            'normalized_key' => $candidate['normalized_key'],
            'description' => $candidate['description'],
            'avg_amount' => $candidate['avg_amount'],
            'occurrences' => $candidate['occurrences'],
            'last_seen_date' => $candidate['last_seen_date'],
            'suggested_due_day' => $candidate['suggested_due_day'],
            'sample_expense_ids' => $candidate['sample_expense_ids'],
        ]);
    }

    public function list(int $workspaceId, string $status): array
    {
        return $this->call('sp_smart_suggestion_list', ['workspace_id' => $workspaceId, 'status' => $status]);
    }

    public function find(int $workspaceId, int $suggestionId): ?array
    {
        try {
            return $this->call('sp_smart_suggestion_get', [
                'workspace_id' => $workspaceId,
                'suggestion_id' => $suggestionId,
            ]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }

            throw $e;
        }
    }

    public function updateStatus(
        int $workspaceId,
        int $suggestionId,
        string $status,
        ?int $serviceId,
        int $resolvedBy,
    ): array {
        return $this->call('sp_smart_suggestion_update_status', [
            'workspace_id' => $workspaceId,
            'suggestion_id' => $suggestionId,
            'status' => $status,
            'service_id' => $serviceId,
            'resolved_by' => $resolvedBy,
        ]);
    }
}
