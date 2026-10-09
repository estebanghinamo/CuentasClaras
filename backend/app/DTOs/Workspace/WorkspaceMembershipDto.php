<?php

namespace App\DTOs\Workspace;

final readonly class WorkspaceMembershipDto
{
    public function __construct(
        public int $workspaceId,
        public int $userId,
        public string $role,
        public string $workspaceType,
        public string $workspaceCurrency,
        public string $workspaceName,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            workspaceId: (int) $row['workspace_id'],
            userId: (int) $row['user_id'],
            role: (string) $row['role'],
            workspaceType: (string) $row['workspace_type'],
            workspaceCurrency: (string) $row['workspace_currency'],
            workspaceName: (string) $row['workspace_name'],
        );
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
