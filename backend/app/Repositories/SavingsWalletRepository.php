<?php

namespace App\Repositories;

class SavingsWalletRepository extends BaseRepository
{
    public function get(int $workspaceId): array
    {
        return $this->call('sp_savings_wallet_get', ['workspace_id' => $workspaceId]);
    }

    public function createMovement(int $workspaceId, array $data, array $audit): array
    {
        return $this->call('sp_savings_movement_create', [
            'workspace_id' => $workspaceId,
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => (string) $data['amount'],
            'year' => $data['year'],
            'month' => $data['month'],
            'note' => $data['note'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'closing_id' => $data['closing_id'] ?? null,
            'audit' => $audit,
        ]);
    }

    public function listMovements(int $workspaceId, int $page, int $perPage, ?string $type): array
    {
        return $this->call('sp_savings_movement_list', [
            'workspace_id' => $workspaceId,
            'page' => $page,
            'per_page' => $perPage,
            'type' => $type,
        ]);
    }

    public function history(int $workspaceId): array
    {
        return $this->call('sp_savings_wallet_history', ['workspace_id' => $workspaceId]);
    }

    public function totalsForPeriod(int $workspaceId, int $year, int $month): array
    {
        return $this->call('sp_savings_movement_totals_for_period', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
