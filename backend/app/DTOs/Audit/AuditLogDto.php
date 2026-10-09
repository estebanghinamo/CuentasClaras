<?php

namespace App\DTOs\Audit;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuditLog',
    description: 'Entrada del historial de actividad de un espacio',
    required: [
        'id', 'user_id', 'user_name', 'entity_type', 'entity_id', 'action', 'summary',
        'old_value', 'new_value', 'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'entity_type', type: 'string', example: 'expense'),
        new OA\Property(property: 'entity_id', type: 'integer', example: 42),
        new OA\Property(property: 'action', type: 'string', example: 'created'),
        new OA\Property(property: 'summary', type: 'string', example: 'Creo el gasto "Supermercado" por $15000'),
        new OA\Property(property: 'old_value', type: 'object', nullable: true, additionalProperties: true),
        new OA\Property(property: 'new_value', type: 'object', nullable: true, additionalProperties: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class AuditLogDto
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $userName,
        public string $entityType,
        public int $entityId,
        public string $action,
        public string $summary,
        public ?array $oldValue,
        public ?array $newValue,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            userName: (string) $row['user_name'],
            entityType: (string) $row['entity_type'],
            entityId: (int) $row['entity_id'],
            action: (string) $row['action'],
            summary: (string) $row['summary'],
            oldValue: $row['old_value'] ?? null,
            newValue: $row['new_value'] ?? null,
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'action' => $this->action,
            'summary' => $this->summary,
            'old_value' => $this->oldValue,
            'new_value' => $this->newValue,
            'created_at' => $this->createdAt,
        ];
    }
}
