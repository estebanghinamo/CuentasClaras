<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ServicePayment',
    required: [
        'id', 'service_id', 'service_name', 'year', 'month', 'expected_amount',
        'due_date_start', 'due_date_end', 'status', 'amount_paid', 'late_fee_applied',
        'paid_at', 'was_late', 'paid_by_user_id', 'paid_by_name', 'notes', 'days_until_due',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'service_id', type: 'integer', example: 1),
        new OA\Property(property: 'service_name', type: 'string', example: 'Luz'),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', minimum: 1, maximum: 12, example: 9),
        new OA\Property(property: 'expected_amount', type: 'number', format: 'float', example: 15000.5),
        new OA\Property(property: 'due_date_start', type: 'string', format: 'date'),
        new OA\Property(property: 'due_date_end', type: 'string', format: 'date'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'paid', 'overdue']),
        new OA\Property(property: 'amount_paid', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'late_fee_applied', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'was_late', type: 'boolean'),
        new OA\Property(property: 'paid_by_user_id', type: 'integer', nullable: true),
        new OA\Property(property: 'paid_by_name', type: 'string', nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'days_until_due', type: 'integer', nullable: true, example: 5),
    ],
    type: 'object',
)]
final readonly class ServicePaymentDto
{
    public function __construct(
        public int $id,
        public int $serviceId,
        public string $serviceName,
        public int $year,
        public int $month,
        public float $expectedAmount,
        public string $dueDateStart,
        public string $dueDateEnd,
        public string $status,
        public ?float $amountPaid,
        public float $lateFeeApplied,
        public ?string $paidAt,
        public bool $wasLate,
        public ?int $paidByUserId,
        public ?string $paidByName,
        public ?string $notes,
        public ?int $daysUntilDue,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            serviceId: (int) $row['service_id'],
            serviceName: (string) $row['service_name'],
            year: (int) $row['year'],
            month: (int) $row['month'],
            expectedAmount: (float) $row['expected_amount'],
            dueDateStart: (string) $row['due_date_start'],
            dueDateEnd: (string) $row['due_date_end'],
            status: (string) $row['status'],
            amountPaid: isset($row['amount_paid']) ? (float) $row['amount_paid'] : null,
            lateFeeApplied: (float) $row['late_fee_applied'],
            paidAt: $row['paid_at'] ?? null,
            wasLate: (bool) $row['was_late'],
            paidByUserId: isset($row['paid_by_user_id']) ? (int) $row['paid_by_user_id'] : null,
            paidByName: $row['paid_by_name'] ?? null,
            notes: $row['notes'] ?? null,
            daysUntilDue: isset($row['days_until_due']) ? (int) $row['days_until_due'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->serviceId,
            'service_name' => $this->serviceName,
            'year' => $this->year,
            'month' => $this->month,
            'expected_amount' => $this->expectedAmount,
            'due_date_start' => $this->dueDateStart,
            'due_date_end' => $this->dueDateEnd,
            'status' => $this->status,
            'amount_paid' => $this->amountPaid,
            'late_fee_applied' => $this->lateFeeApplied,
            'paid_at' => $this->paidAt,
            'was_late' => $this->wasLate,
            'paid_by_user_id' => $this->paidByUserId,
            'paid_by_name' => $this->paidByName,
            'notes' => $this->notes,
            'days_until_due' => $this->daysUntilDue,
        ];
    }
}
