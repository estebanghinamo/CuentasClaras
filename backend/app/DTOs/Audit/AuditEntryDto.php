<?php

namespace App\DTOs\Audit;

final readonly class AuditEntryDto
{
    public function __construct(
        public int $workspaceId,
        public int $userId,
        public string $entityType,
        public int $entityId,
        public string $action,
        public string $summary,
        public ?array $oldValue = null,
        public ?array $newValue = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
