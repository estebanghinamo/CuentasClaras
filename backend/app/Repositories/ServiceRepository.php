<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class ServiceRepository extends BaseRepository
{
    public function list(int $workspaceId, string $active): array
    {
        return $this->call('sp_service_list', ['workspace_id' => $workspaceId, 'active' => $active]);
    }

    public function find(int $workspaceId, int $serviceId): ?array
    {
        try {
            return $this->call('sp_service_get', ['workspace_id' => $workspaceId, 'service_id' => $serviceId]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function create(int $workspaceId, array $data, int $year, int $month, array $audit): array
    {
        return $this->call('sp_service_create', [
            'workspace_id' => $workspaceId,
            'name' => $data['name'],
            'amount' => (string) $data['amount'],
            'is_estimated' => $data['is_estimated'],
            'due_day_start' => $data['due_day_start'],
            'due_day_end' => $data['due_day_end'] ?? null,
            'late_fee_type' => $data['late_fee_type'],
            'late_fee_value' => (string) $data['late_fee_value'],
            'year' => $year,
            'month' => $month,
            'audit' => $audit,
        ]);
    }

    public function update(int $workspaceId, int $serviceId, array $data, array $audit): array
    {
        return $this->call('sp_service_update', [
            'workspace_id' => $workspaceId,
            'service_id' => $serviceId,
            'name' => $data['name'],
            'amount' => (string) $data['amount'],
            'is_estimated' => $data['is_estimated'],
            'due_day_start' => $data['due_day_start'],
            'due_day_end' => $data['due_day_end'] ?? null,
            'late_fee_type' => $data['late_fee_type'],
            'late_fee_value' => (string) $data['late_fee_value'],
            'active' => $data['active'],
            'audit' => $audit,
        ]);
    }

    public function delete(int $workspaceId, int $serviceId, array $audit): void
    {
        $this->call('sp_service_delete', [
            'workspace_id' => $workspaceId,
            'service_id' => $serviceId,
            'audit' => $audit,
        ]);
    }
}
