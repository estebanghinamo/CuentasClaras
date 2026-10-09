<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Installment',
    required: [
        'id', 'user_id', 'user_name', 'description', 'category_id', 'category_name',
        'total_amount', 'installments_count', 'installment_amount', 'start_date', 'status',
        'paid_count', 'remaining_count', 'remaining_amount', 'next_due', 'can_edit',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'description', type: 'string', example: 'Heladera nueva'),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true),
        new OA\Property(property: 'category_name', type: 'string', nullable: true),
        new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 60000),
        new OA\Property(property: 'installments_count', type: 'integer', example: 12),
        new OA\Property(property: 'installment_amount', type: 'number', format: 'float', example: 5000),
        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'cancelled']),
        new OA\Property(property: 'paid_count', type: 'integer', example: 3),
        new OA\Property(property: 'remaining_count', type: 'integer', example: 9),
        new OA\Property(property: 'remaining_amount', type: 'number', format: 'float', example: 45000),
        new OA\Property(
            property: 'next_due',
            type: 'object',
            nullable: true,
            description: 'Proxima cuota pendiente (forma libre, generada por el servicio)',
        ),
        new OA\Property(property: 'can_edit', type: 'boolean'),
        new OA\Property(
            property: 'payments',
            type: 'array',
            nullable: true,
            items: new OA\Items(ref: '#/components/schemas/InstallmentPayment'),
            description: 'Detalle de cuotas, solo presente en el endpoint show',
        ),
    ],
    type: 'object',
)]
final readonly class InstallmentDto
{
    /** @param InstallmentPaymentDto[]|null $payments */
    public function __construct(
        public int $id,
        public int $userId,
        public string $userName,
        public string $description,
        public ?int $categoryId,
        public ?string $categoryName,
        public float $totalAmount,
        public int $installmentsCount,
        public float $installmentAmount,
        public string $startDate,
        public string $status,
        public int $paidCount,
        public int $remainingCount,
        public float $remainingAmount,
        public ?array $nextDue,
        public bool $canEdit,
        public ?array $payments = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            userName: (string) $row['user_name'],
            description: (string) $row['description'],
            categoryId: isset($row['category_id']) ? (int) $row['category_id'] : null,
            categoryName: $row['category_name'] ?? null,
            totalAmount: (float) $row['total_amount'],
            installmentsCount: (int) $row['installments_count'],
            installmentAmount: (float) $row['installment_amount'],
            startDate: (string) $row['start_date'],
            status: (string) $row['status'],
            paidCount: (int) $row['paid_count'],
            remainingCount: (int) $row['remaining_count'],
            remainingAmount: (float) $row['remaining_amount'],
            nextDue: $row['next_due'] ?? null,
            canEdit: (bool) ($row['can_edit'] ?? false),
            payments: isset($row['payments']) ? array_map(
                fn (array $payment) => InstallmentPaymentDto::fromArray($payment),
                $row['payments'],
            ) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'total_amount' => $this->totalAmount,
            'installments_count' => $this->installmentsCount,
            'installment_amount' => $this->installmentAmount,
            'start_date' => $this->startDate,
            'status' => $this->status,
            'paid_count' => $this->paidCount,
            'remaining_count' => $this->remainingCount,
            'remaining_amount' => $this->remainingAmount,
            'next_due' => $this->nextDue,
            'can_edit' => $this->canEdit,
            'payments' => $this->payments === null
                ? null
                : array_map(fn (InstallmentPaymentDto $payment) => $payment->toArray(), $this->payments),
        ];
    }
}
