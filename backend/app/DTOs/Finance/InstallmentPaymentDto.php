<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InstallmentPayment',
    required: ['id', 'number', 'year', 'month', 'amount', 'status', 'paid_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(
            property: 'number',
            type: 'integer',
            example: 1,
            description: 'Numero de cuota dentro del plan (1-based)',
        ),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', minimum: 1, maximum: 12, example: 9),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 5000),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'paid']),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date', nullable: true),
    ],
    type: 'object',
)]
final readonly class InstallmentPaymentDto
{
    public function __construct(
        public int $id,
        public int $number,
        public int $year,
        public int $month,
        public float $amount,
        public string $status,
        public ?string $paidAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            number: (int) $row['number'],
            year: (int) $row['year'],
            month: (int) $row['month'],
            amount: (float) $row['amount'],
            status: (string) $row['status'],
            paidAt: $row['paid_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'year' => $this->year,
            'month' => $this->month,
            'amount' => $this->amount,
            'status' => $this->status,
            'paid_at' => $this->paidAt,
        ];
    }
}
