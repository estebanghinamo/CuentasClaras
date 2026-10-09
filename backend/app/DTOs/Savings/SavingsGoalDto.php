<?php

namespace App\DTOs\Savings;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SavingsGoal',
    description: 'Meta de ahorro dentro de un workspace',
    required: [
        'id', 'name', 'target_amount', 'current_amount', 'due_date', 'status', 'progress_pct',
        'remaining_amount', 'days_left', 'suggested_monthly', 'completed_at', 'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Viaje a Bariloche'),
        new OA\Property(property: 'target_amount', type: 'number', format: 'float', example: 100000),
        new OA\Property(property: 'current_amount', type: 'number', format: 'float', example: 25000),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'cancelled']),
        new OA\Property(property: 'progress_pct', type: 'number', format: 'float', example: 25.0),
        new OA\Property(property: 'remaining_amount', type: 'number', format: 'float', example: 75000),
        new OA\Property(property: 'days_left', type: 'integer', nullable: true, example: 94),
        new OA\Property(
            property: 'suggested_monthly',
            type: 'number',
            format: 'float',
            nullable: true,
            example: 23809.52,
        ),
        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class SavingsGoalDto
{
    public function __construct(
        public int $id,
        public string $name,
        public float $targetAmount,
        public float $currentAmount,
        public ?string $dueDate,
        public string $status,
        public float $progressPct,
        public float $remainingAmount,
        public ?int $daysLeft,
        public ?float $suggestedMonthly,
        public ?string $completedAt,
        public string $createdAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_amount' => $this->targetAmount,
            'current_amount' => $this->currentAmount,
            'due_date' => $this->dueDate,
            'status' => $this->status,
            'progress_pct' => $this->progressPct,
            'remaining_amount' => $this->remainingAmount,
            'days_left' => $this->daysLeft,
            'suggested_monthly' => $this->suggestedMonthly,
            'completed_at' => $this->completedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
