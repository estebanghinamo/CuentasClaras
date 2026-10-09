<?php

namespace App\DTOs\Notifications;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Notification',
    required: ['id', 'type', 'title', 'body', 'route', 'workspace_id', 'payload', 'read_at', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'service_due_soon'),
        new OA\Property(property: 'title', type: 'string', example: 'Vencimiento proximo'),
        new OA\Property(property: 'body', type: 'string', example: 'El servicio Luz vence en 3 dias'),
        new OA\Property(property: 'route', type: 'string', nullable: true, example: '/services/1'),
        new OA\Property(property: 'workspace_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'payload', type: 'object', additionalProperties: true),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class NotificationDto
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $body,
        public ?string $route,
        public ?int $workspaceId,
        public array $payload,
        public ?string $readAt,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            type: (string) $row['type'],
            title: (string) $row['title'],
            body: (string) $row['body'],
            route: $row['route'] ?? null,
            workspaceId: isset($row['workspace_id']) ? (int) $row['workspace_id'] : null,
            payload: $row['payload'] ?? [],
            readAt: $row['read_at'] ?? null,
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'route' => $this->route,
            'workspace_id' => $this->workspaceId,
            'payload' => $this->payload,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
        ];
    }
}
