<?php

namespace App\DTOs\Savings;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SavingsMovement',
    description: 'Movimiento de la billetera de ahorro (deposito o retiro)',
    required: ['id', 'type', 'amount', 'balance_after', 'year', 'month', 'note', 'source', 'user_name', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', enum: ['deposit', 'withdraw']),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 5000),
        new OA\Property(property: 'balance_after', type: 'number', format: 'float', example: 15000.50),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', example: 9),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Ahorro mensual'),
        new OA\Property(property: 'source', type: 'string', example: 'manual'),
        new OA\Property(property: 'user_name', type: 'string', nullable: true, example: 'Ana Gomez'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class SavingsMovementDto
{
    public function __construct(
        public int $id,
        public string $type,
        public float $amount,
        public float $balanceAfter,
        public int $year,
        public int $month,
        public ?string $note,
        public string $source,
        public ?string $userName,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            type: (string) $row['type'],
            amount: (float) $row['amount'],
            balanceAfter: (float) $row['balance_after'],
            year: (int) $row['year'],
            month: (int) $row['month'],
            note: $row['note'] ?? null,
            source: (string) $row['source'],
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
            'balance_after' => $this->balanceAfter,
            'year' => $this->year,
            'month' => $this->month,
            'note' => $this->note,
            'source' => $this->source,
            'user_name' => $this->userName,
            'created_at' => $this->createdAt,
        ];
    }
}
