<?php

namespace App\DTOs\Reports;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Dashboard',
    required: [
        'year', 'month', 'currency', 'totals', 'by_category', 'paid_services', 'installment_breakdown',
        'by_user', 'comparison', 'overdue_services', 'health', 'pending_allocation', 'projection',
    ],
    properties: [
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', example: 9),
        new OA\Property(property: 'currency', type: 'string', example: 'ARS'),
        new OA\Property(
            property: 'totals',
            required: [
                'income', 'expenses', 'category_expenses', 'services_paid', 'installments', 'installments_paid',
                'committed', 'available', 'savings_deposited', 'savings_withdrawn', 'goals_contributed',
                'goals_withdrawn',
            ],
            properties: [
                new OA\Property(property: 'income', type: 'number', format: 'float'),
                new OA\Property(property: 'expenses', type: 'number', format: 'float'),
                new OA\Property(property: 'category_expenses', type: 'number', format: 'float'),
                new OA\Property(property: 'services_paid', type: 'number', format: 'float'),
                new OA\Property(property: 'installments', type: 'number', format: 'float'),
                new OA\Property(property: 'installments_paid', type: 'number', format: 'float'),
                new OA\Property(property: 'committed', type: 'number', format: 'float'),
                new OA\Property(property: 'available', type: 'number', format: 'float'),
                new OA\Property(property: 'savings_deposited', type: 'number', format: 'float'),
                new OA\Property(property: 'savings_withdrawn', type: 'number', format: 'float'),
                new OA\Property(property: 'goals_contributed', type: 'number', format: 'float'),
                new OA\Property(property: 'goals_withdrawn', type: 'number', format: 'float'),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'by_category',
            type: 'array',
            items: new OA\Items(
                required: ['category_id', 'category_name', 'category_color', 'category_icon', 'amount', 'pct'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'category_name', type: 'string'),
                    new OA\Property(property: 'category_color', type: 'string'),
                    new OA\Property(property: 'category_icon', type: 'string'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'pct', type: 'number', format: 'float'),
                ],
                type: 'object',
            ),
        ),
        new OA\Property(
            property: 'paid_services',
            type: 'array',
            items: new OA\Items(
                required: ['service_name', 'amount'],
                properties: [
                    new OA\Property(property: 'service_name', type: 'string'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                ],
                type: 'object',
            ),
        ),
        new OA\Property(
            property: 'installment_breakdown',
            type: 'array',
            items: new OA\Items(
                required: ['description', 'number', 'installments_count', 'amount', 'status'],
                properties: [
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'number', type: 'integer'),
                    new OA\Property(property: 'installments_count', type: 'integer'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'status', type: 'string'),
                ],
                type: 'object',
            ),
        ),
        new OA\Property(
            property: 'by_user',
            type: 'array',
            items: new OA\Items(
                required: ['user_id', 'user_name', 'income', 'expenses', 'installments', 'balance'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'user_name', type: 'string'),
                    new OA\Property(property: 'income', type: 'number', format: 'float'),
                    new OA\Property(property: 'expenses', type: 'number', format: 'float'),
                    new OA\Property(property: 'installments', type: 'number', format: 'float'),
                    new OA\Property(property: 'balance', type: 'number', format: 'float'),
                ],
                type: 'object',
            ),
        ),
        new OA\Property(
            property: 'comparison',
            nullable: true,
            required: ['previous_income', 'previous_expenses', 'income_delta_pct', 'expenses_delta_pct'],
            properties: [
                new OA\Property(property: 'previous_income', type: 'number', format: 'float'),
                new OA\Property(property: 'previous_expenses', type: 'number', format: 'float'),
                new OA\Property(property: 'income_delta_pct', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'expenses_delta_pct', type: 'number', format: 'float', nullable: true),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'overdue_services',
            required: ['count', 'items'],
            properties: [
                new OA\Property(property: 'count', type: 'integer'),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        required: ['service_payment_id', 'service_name', 'amount', 'due_date_end'],
                        properties: [
                            new OA\Property(property: 'service_payment_id', type: 'integer'),
                            new OA\Property(property: 'service_name', type: 'string'),
                            new OA\Property(property: 'amount', type: 'number', format: 'float'),
                            new OA\Property(property: 'due_date_end', type: 'string', format: 'date'),
                        ],
                        type: 'object',
                    ),
                ),
            ],
            type: 'object',
        ),
        new OA\Property(property: 'health', ref: '#/components/schemas/HealthScore', nullable: true, type: 'object'),
        new OA\Property(
            property: 'pending_allocation',
            nullable: true,
            required: ['year', 'month', 'unallocated_amount'],
            properties: [
                new OA\Property(property: 'year', type: 'integer'),
                new OA\Property(property: 'month', type: 'integer'),
                new OA\Property(property: 'unallocated_amount', type: 'number', format: 'float'),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'projection',
            nullable: true,
            required: [
                'days_elapsed', 'days_in_month', 'daily_average', 'projected_expenses',
                'projected_available', 'trend',
            ],
            properties: [
                new OA\Property(property: 'days_elapsed', type: 'integer'),
                new OA\Property(property: 'days_in_month', type: 'integer'),
                new OA\Property(property: 'daily_average', type: 'number', format: 'float'),
                new OA\Property(property: 'projected_expenses', type: 'number', format: 'float'),
                new OA\Property(property: 'projected_available', type: 'number', format: 'float'),
                new OA\Property(property: 'trend', type: 'string'),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
final readonly class DashboardDto
{
    /**
    * @param array{
    *     category_id:?int,category_name:string,category_color:string,category_icon:string,amount:float,pct:float
    * }[] $byCategory
    * @param array{service_name:string,amount:float}[] $paidServices
    * @param array{
    *     description:string,number:int,installments_count:int,amount:float,status:string
    * }[] $installmentBreakdown
    * @param array{user_id:int,user_name:string,income:float,expenses:float,installments:float,balance:float}[] $byUser
     * @param array{
     *     previous_income:float,previous_expenses:float,income_delta_pct:?float,expenses_delta_pct:?float
     * }|null $comparison
     * @param array{service_payment_id:int,service_name:string,amount:float,due_date_end:string}[] $overdueServices
     * @param array{year:int,month:int,unallocated_amount:float}|null $pendingAllocation
     * @param array{
     *     days_elapsed:int,days_in_month:int,daily_average:float,projected_expenses:float,
     *     projected_available:float,trend:string
     * }|null $projection
     */
    public function __construct(
        public int $year,
        public int $month,
        public string $currency,
        public float $totalIncome,
        public float $totalExpenses,
        public float $categoryExpenses,
        public float $servicesPaid,
        public float $installments,
        public float $installmentsPaid,
        public float $committed,
        public float $available,
        public float $savingsDeposited,
        public float $savingsWithdrawn,
        public float $goalsContributed,
        public float $goalsWithdrawn,
        public array $byCategory,
        public array $paidServices,
        public array $installmentBreakdown,
        public array $byUser,
        public ?array $comparison,
        public array $overdueServices,
        public ?HealthScoreDto $health,
        public ?array $pendingAllocation,
        public ?array $projection,
    ) {}

    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'currency' => $this->currency,
            'totals' => [
                'income' => $this->totalIncome,
                'expenses' => $this->totalExpenses,
                'category_expenses' => $this->categoryExpenses,
                'services_paid' => $this->servicesPaid,
                'installments' => $this->installments,
                'installments_paid' => $this->installmentsPaid,
                'committed' => $this->committed,
                'available' => $this->available,
                'savings_deposited' => $this->savingsDeposited,
                'savings_withdrawn' => $this->savingsWithdrawn,
                'goals_contributed' => $this->goalsContributed,
                'goals_withdrawn' => $this->goalsWithdrawn,
            ],
            'by_category' => $this->byCategory,
            'paid_services' => $this->paidServices,
            'installment_breakdown' => $this->installmentBreakdown,
            'by_user' => $this->byUser,
            'comparison' => $this->comparison,
            'overdue_services' => [
                'count' => count($this->overdueServices),
                'items' => $this->overdueServices,
            ],
            'health' => $this->health?->toArray(),
            'pending_allocation' => $this->pendingAllocation,
            'projection' => $this->projection,
        ];
    }
}
