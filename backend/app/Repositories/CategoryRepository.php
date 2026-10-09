<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class CategoryRepository extends BaseRepository
{
    public function list(int $workspaceId): array
    {
        return $this->call('sp_category_list', ['workspace_id' => $workspaceId]);
    }

    public function find(int $workspaceId, int $categoryId): ?array
    {
        try {
            return $this->call('sp_category_get', ['workspace_id' => $workspaceId, 'category_id' => $categoryId]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function create(int $workspaceId, string $name, string $icon, string $color, array $audit): array
    {
        return $this->call('sp_category_create', [
            'workspace_id' => $workspaceId,
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
            'audit' => $audit,
        ]);
    }

    public function update(
        int $workspaceId,
        int $categoryId,
        string $name,
        string $icon,
        string $color,
        array $audit,
    ): array {
        return $this->call('sp_category_update', [
            'workspace_id' => $workspaceId,
            'category_id' => $categoryId,
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
            'audit' => $audit,
        ]);
    }

    public function delete(int $workspaceId, int $categoryId, array $audit): void
    {
        $this->call('sp_category_delete', [
            'workspace_id' => $workspaceId,
            'category_id' => $categoryId,
            'audit' => $audit,
        ]);
    }
}
