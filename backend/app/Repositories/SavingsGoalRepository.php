<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class SavingsGoalRepository extends BaseRepository
{
    public function list(int $workspaceId, string $status): array
    {
        return $this->call('sp_savings_goal_list', ['workspace_id' => $workspaceId, 'status' => $status]);
    }

    public function find(int $workspaceId, int $goalId): ?array
    {
        try {
            return $this->call('sp_savings_goal_get', ['workspace_id' => $workspaceId, 'goal_id' => $goalId]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function create(int $workspaceId, array $data, array $audit): array
    {
        return $this->call('sp_savings_goal_create', [
            'workspace_id' => $workspaceId,
            'name' => $data['name'],
            'target_amount' => (string) $data['target_amount'],
            'due_date' => $data['due_date'] ?? null,
            'audit' => $audit,
        ]);
    }

    public function update(int $workspaceId, int $goalId, array $data, array $audit): array
    {
        return $this->call('sp_savings_goal_update', [
            'workspace_id' => $workspaceId,
            'goal_id' => $goalId,
            'name' => $data['name'],
            'target_amount' => (string) $data['target_amount'],
            'due_date' => $data['due_date'] ?? null,
            'audit' => $audit,
        ]);
    }

    public function cancel(int $workspaceId, int $goalId, array $audit): array
    {
        return $this->call('sp_savings_goal_cancel', [
            'workspace_id' => $workspaceId,
            'goal_id' => $goalId,
            'audit' => $audit,
        ]);
    }

    public function contribute(int $workspaceId, int $goalId, array $data, array $audit): array
    {
        return $this->call('sp_savings_goal_contribute', [
            'workspace_id' => $workspaceId,
            'goal_id' => $goalId,
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => (string) $data['amount'],
            'source' => $data['source'] ?? 'manual',
            'closing_id' => $data['closing_id'] ?? null,
            'note' => $data['note'] ?? null,
            'audit' => $audit,
        ]);
    }

    public function movements(int $workspaceId, int $goalId): array
    {
        return $this->call('sp_savings_goal_movements', ['workspace_id' => $workspaceId, 'goal_id' => $goalId]);
    }

    public function totalsForPeriod(int $workspaceId, int $year, int $month): array
    {
        return $this->call('sp_savings_goal_totals_for_period', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function transferFromWallet(int $workspaceId, int $goalId, array $data, array $audit): array
    {
        return $this->call('sp_savings_transfer_wallet_to_goal', [
            'workspace_id' => $workspaceId,
            'goal_id' => $goalId,
            'user_id' => $data['user_id'],
            'amount' => (string) $data['amount'],
            'year' => $data['year'],
            'month' => $data['month'],
            'note' => $data['note'] ?? null,
            'audit' => $audit,
        ]);
    }
}
