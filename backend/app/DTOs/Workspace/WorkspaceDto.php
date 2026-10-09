<?php

namespace App\DTOs\Workspace;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Workspace',
    required: [
        'id', 'name', 'currency', 'type', 'created_by', 'role', 'members_count',
        'onboarding_completed', 'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Finanzas del hogar'),
        new OA\Property(property: 'currency', type: 'string', example: 'ARS'),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['individual', 'shared_joint', 'shared_separate', 'shared_settlement'],
        ),
        new OA\Property(property: 'created_by', type: 'integer', example: 1),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'member']),
        new OA\Property(property: 'members_count', type: 'integer', example: 2),
        new OA\Property(property: 'onboarding_completed', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class WorkspaceDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $currency,
        public string $type,
        public int $createdBy,
        public string $role,
        public int $membersCount,
        public bool $onboardingCompleted,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            currency: (string) $row['currency'],
            type: (string) $row['type'],
            createdBy: (int) $row['created_by'],
            role: (string) $row['role'],
            membersCount: (int) $row['members_count'],
            onboardingCompleted: !empty($row['onboarding_completed']),
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency,
            'type' => $this->type,
            'created_by' => $this->createdBy,
            'role' => $this->role,
            'members_count' => $this->membersCount,
            'onboarding_completed' => $this->onboardingCompleted,
            'created_at' => $this->createdAt,
        ];
    }
}
