<?php

namespace App\Repositories;

use App\DTOs\Audit\AuditEntryDto;

class AuditLogRepository extends BaseRepository
{
    public function create(AuditEntryDto $entry): void
    {
        $this->call('sp_audit_log_create', [
            'workspace_id' => $entry->workspaceId,
            'user_id' => $entry->userId,
            'entity_type' => $entry->entityType,
            'entity_id' => $entry->entityId,
            'action' => $entry->action,
            'summary' => $entry->summary,
            'old_value' => $entry->oldValue,
            'new_value' => $entry->newValue,
            'ip_address' => $entry->ipAddress,
            'user_agent' => $entry->userAgent,
        ]);
    }

    public function listByWorkspace(int $workspaceId, int $page, int $perPage, ?string $entityType, ?int $userId): array
    {
        return $this->call('sp_audit_log_list', [
            'workspace_id' => $workspaceId,
            'page' => $page,
            'per_page' => $perPage,
            'entity_type' => $entityType,
            'user_id' => $userId,
        ]);
    }
}
