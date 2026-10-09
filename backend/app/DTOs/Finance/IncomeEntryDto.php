<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'IncomeEntry',
    required: [
        'id', 'user_id', 'user_name', 'amount', 'concept', 'date', 'year', 'month',
        'source', 'created_at', 'can_edit',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150000.5),
        new OA\Property(property: 'concept', type: 'string', example: 'Sueldo'),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-09-01'),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', example: 9),
        new OA\Property(property: 'source', type: 'string', example: 'manual'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'can_edit', type: 'boolean', example: true),
    ],
    type: 'object',
)]
final readonly class IncomeEntryDto
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $userName,
        public float $amount,
        public string $concept,
        public string $date,
        public int $year,
        public int $month,
        public string $source,
        public string $createdAt,
        public bool $canEdit,
    ) {}

    public static function fromArray(array $row, bool $canEdit): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            userName: (string) $row['user_name'],
            amount: (float) $row['amount'],
            concept: (string) $row['concept'],
            date: (string) $row['date'],
            year: (int) $row['year'],
            month: (int) $row['month'],
            source: (string) ($row['source'] ?? 'manual'),
            createdAt: (string) $row['created_at'],
            canEdit: $canEdit,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'amount' => $this->amount,
            'concept' => $this->concept,
            'date' => $this->date,
            'year' => $this->year,
            'month' => $this->month,
            'source' => $this->source,
            'created_at' => $this->createdAt,
            'can_edit' => $this->canEdit,
        ];
    }
}
