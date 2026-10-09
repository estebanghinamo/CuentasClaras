<?php

namespace App\DTOs\Workspace;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WorkspaceMember',
    required: ['user_id', 'name', 'email', 'role', 'joined_at'],
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'member']),
        new OA\Property(property: 'joined_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
final readonly class WorkspaceMemberDto
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
        public string $role,
        public ?string $joinedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            userId: (int) $row['user_id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            role: (string) $row['role'],
            joinedAt: $row['joined_at'] !== null ? (string) $row['joined_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'joined_at' => $this->joinedAt,
        ];
    }
}
