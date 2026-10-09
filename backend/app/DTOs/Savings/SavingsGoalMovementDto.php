<?php

namespace App\DTOs\Savings;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SavingsGoalMovement',
    description: 'Movimiento de aporte o retiro sobre una meta de ahorro',
    required: ['id', 'type', 'amount', 'source', 'note', 'user_name', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', enum: ['contribution', 'withdrawal']),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 5000),
        new OA\Property(property: 'source', type: 'string', example: 'manual'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Aporte extra'),
        new OA\Property(property: 'user_name', type: 'string', nullable: true, example: 'Ana Gomez'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class SavingsGoalMovementDto
{
    public function __construct(
        public int $id,
        public string $type,
        public float $amount,
        public string $source,
        public ?string $note,
        public ?string $userName,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            type: (string) $row['type'],
            amount: (float) $row['amount'],
            source: (string) $row['source'],
            note: $row['note'] ?? null,
            userName: $row['user_name'] ?? null,
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'source' => $this->source,
            'note' => $this->note,
            'user_name' => $this->userName,
            'created_at' => $this->createdAt,
        ];
    }
}
