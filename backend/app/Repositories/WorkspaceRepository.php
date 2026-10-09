<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class WorkspaceRepository extends BaseRepository
{
    public function create(string $name, string $currency, string $type, int $createdBy): array
    {
        return $this->call('sp_workspace_create', [
            'name' => $name,
            'currency' => $currency,
            'type' => $type,
            'created_by' => $createdBy,
        ]);
    }

    /** Todos los workspaces del sistema (para jobs que iteran, ej. CloseMonthJob - M-13). */
    public function listAllIds(): array
    {
        return array_map(fn (array $row) => (int) $row['id'], $this->call('sp_workspace_list_all', []));
    }

    public function listByUser(int $userId): array
    {
        return $this->call('sp_workspace_list_by_user', ['user_id' => $userId]);
    }

    public function find(int $workspaceId, int $userId): ?array
    {
        try {
            return $this->call('sp_workspace_get', ['workspace_id' => $workspaceId, 'user_id' => $userId]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function update(int $workspaceId, string $name, string $currency, string $type): array
    {
        return $this->call('sp_workspace_update', [
            'workspace_id' => $workspaceId,
            'name' => $name,
            'currency' => $currency,
            'type' => $type,
        ]);
    }

    public function delete(int $workspaceId): void
    {
        $this->call('sp_workspace_delete', ['workspace_id' => $workspaceId]);
    }

    public function getMembership(int $workspaceId, int $userId): ?array
    {
        try {
            return $this->call('sp_workspace_member_get', ['workspace_id' => $workspaceId, 'user_id' => $userId]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function listMembers(int $workspaceId): array
    {
        return $this->call('sp_workspace_members_list', ['workspace_id' => $workspaceId]);
    }

    public function removeMember(int $workspaceId, int $userId): void
    {
        $this->call('sp_workspace_member_remove', ['workspace_id' => $workspaceId, 'user_id' => $userId]);
    }

    public function markOnboarding(int $workspaceId): array
    {
        return $this->call('sp_workspace_mark_onboarding', ['workspace_id' => $workspaceId]);
    }
}
