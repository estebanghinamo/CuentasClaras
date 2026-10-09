<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Service',
    required: [
        'id', 'name', 'amount', 'is_estimated', 'due_day_start', 'due_day_end',
        'late_fee_type', 'late_fee_value', 'active', 'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Luz'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 15000.5),
        new OA\Property(
            property: 'is_estimated',
            type: 'boolean',
            description: 'El monto es una estimacion (puede variar mes a mes)',
        ),
        new OA\Property(property: 'due_day_start', type: 'integer', minimum: 1, maximum: 31, example: 10),
        new OA\Property(property: 'due_day_end', type: 'integer', minimum: 1, maximum: 31, nullable: true, example: 15),
        new OA\Property(property: 'late_fee_type', type: 'string', enum: ['percentage', 'fixed']),
        new OA\Property(property: 'late_fee_value', type: 'number', format: 'float', example: 10),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class ServiceDto
{
    public function __construct(
        public int $id,
        public string $name,
        public float $amount,
        public bool $isEstimated,
        public int $dueDayStart,
        public ?int $dueDayEnd,
        public string $lateFeeType,
        public float $lateFeeValue,
        public bool $active,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            amount: (float) $row['amount'],
            isEstimated: (bool) $row['is_estimated'],
            dueDayStart: (int) $row['due_day_start'],
            dueDayEnd: isset($row['due_day_end']) ? (int) $row['due_day_end'] : null,
            lateFeeType: (string) $row['late_fee_type'],
            lateFeeValue: (float) $row['late_fee_value'],
            active: (bool) $row['active'],
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'is_estimated' => $this->isEstimated,
            'due_day_start' => $this->dueDayStart,
            'due_day_end' => $this->dueDayEnd,
            'late_fee_type' => $this->lateFeeType,
            'late_fee_value' => $this->lateFeeValue,
            'active' => $this->active,
            'created_at' => $this->createdAt,
        ];
    }
}
