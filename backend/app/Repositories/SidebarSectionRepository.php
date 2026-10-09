<?php

namespace App\Repositories;

class SidebarSectionRepository extends BaseRepository
{
    public function list(int $workspaceId, int $userId): array
    {
        return $this->call('sp_sidebar_sections_list', ['workspace_id' => $workspaceId, 'user_id' => $userId]);
    }

    public function set(int $workspaceId, int $userId, array $sections): array
    {
        return $this->call('sp_sidebar_sections_set', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'sections' => $sections,
        ]);
    }
}
