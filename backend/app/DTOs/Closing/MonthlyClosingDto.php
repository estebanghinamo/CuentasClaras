<?php

namespace App\DTOs\Closing;

use App\Support\Money;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MonthlyClosing',
    required: [
        'id', 'year', 'month', 'total_income', 'total_expenses', 'total_services', 'total_installments',
        'remaining_amount', 'savings_generated', 'allocated_to_wallet', 'allocated_to_goals',
        'allocated_to_next_month', 'unallocated_amount', 'allocation_status', 'allocated_at',
        'closed_by', 'closed_at', 'health_score', 'breakdown', 'next_month_open',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', example: 9),
        new OA\Property(property: 'total_income', type: 'number', format: 'float'),
        new OA\Property(property: 'total_expenses', type: 'number', format: 'float'),
        new OA\Property(property: 'total_services', type: 'number', format: 'float'),
        new OA\Property(property: 'total_installments', type: 'number', format: 'float'),
        new OA\Property(property: 'remaining_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'savings_generated', type: 'number', format: 'float'),
        new OA\Property(property: 'allocated_to_wallet', type: 'number', format: 'float'),
        new OA\Property(property: 'allocated_to_goals', type: 'number', format: 'float'),
        new OA\Property(property: 'allocated_to_next_month', type: 'number', format: 'float'),
        new OA\Property(property: 'unallocated_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'allocation_status', type: 'string', enum: ['pending', 'allocated']),
        new OA\Property(property: 'allocated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'closed_by', type: 'string'),
        new OA\Property(property: 'closed_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'health_score', type: 'integer', nullable: true, minimum: 0, maximum: 100),
        new OA\Property(property: 'breakdown', type: 'object', nullable: true, additionalProperties: true),
        new OA\Property(
            property: 'next_month_open',
            type: 'boolean',
            description: 'false si el mes siguiente ya esta cerrado (no admite mas ingresos)',
        ),
    ],
    type: 'object',
)]
final readonly class MonthlyClosingDto
{
    public function __construct(
        public int $id,
        public int $year,
        public int $month,
        public string $totalIncome,
        public string $totalExpenses,
        public string $totalServices,
        public string $totalInstallments,
        public string $remainingAmount,
        public string $savingsGenerated,
        public string $allocatedToWallet,
        public string $allocatedToGoals,
        public string $allocatedToNextMonth,
        public string $allocationStatus,
        public ?string $allocatedAt,
        public string $closedBy,
        public string $closedAt,
        public ?int $healthScore,
        public ?array $breakdown = null,
        /** Solo lo pueblan get()/allocate() - false si el mes siguiente ya está cerrado (no admite más ingresos). */
        public bool $nextMonthOpen = true,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            year: (int) $row['year'],
            month: (int) $row['month'],
            totalIncome: (string) $row['total_income'],
            totalExpenses: (string) $row['total_expenses'],
            totalServices: (string) $row['total_services'],
            totalInstallments: (string) $row['total_installments'],
            remainingAmount: (string) $row['remaining_amount'],
            savingsGenerated: (string) $row['savings_generated'],
            allocatedToWallet: (string) $row['allocated_to_wallet'],
            allocatedToGoals: (string) $row['allocated_to_goals'],
            allocatedToNextMonth: (string) ($row['allocated_to_next_month'] ?? '0'),
            allocationStatus: (string) $row['allocation_status'],
            allocatedAt: $row['allocated_at'] ?? null,
            closedBy: (string) $row['closed_by'],
            closedAt: (string) $row['closed_at'],
            healthScore: isset($row['health_score']) ? (int) $row['health_score'] : null,
            breakdown: $row['breakdown'] ?? null,
            nextMonthOpen: isset($row['next_month_open']) ? (bool) $row['next_month_open'] : true,
        );
    }

    /** unallocated_amount = max(0, remaining) - allocated_to_wallet - allocated_to_goals
     * - allocated_to_next_month (ver §3). */
    public function unallocatedAmount(): string
    {
        $positiveRemaining = Money::cmp($this->remainingAmount, '0') > 0 ? $this->remainingAmount : '0';

        return Money::sub(
            Money::sub(Money::sub($positiveRemaining, $this->allocatedToWallet), $this->allocatedToGoals),
            $this->allocatedToNextMonth,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'month' => $this->month,
            'total_income' => Money::toFloat($this->totalIncome),
            'total_expenses' => Money::toFloat($this->totalExpenses),
            'total_services' => Money::toFloat($this->totalServices),
            'total_installments' => Money::toFloat($this->totalInstallments),
            'remaining_amount' => Money::toFloat($this->remainingAmount),
            'savings_generated' => Money::toFloat($this->savingsGenerated),
            'allocated_to_wallet' => Money::toFloat($this->allocatedToWallet),
            'allocated_to_goals' => Money::toFloat($this->allocatedToGoals),
            'allocated_to_next_month' => Money::toFloat($this->allocatedToNextMonth),
            'unallocated_amount' => Money::toFloat($this->unallocatedAmount()),
            'allocation_status' => $this->allocationStatus,
            'allocated_at' => $this->allocatedAt,
            'closed_by' => $this->closedBy,
            'closed_at' => $this->closedAt,
            'health_score' => $this->healthScore,
            'breakdown' => $this->breakdown,
            'next_month_open' => $this->nextMonthOpen,
        ];
    }
}
