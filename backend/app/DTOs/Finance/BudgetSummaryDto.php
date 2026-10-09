<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BudgetSummary',
    description: 'Resumen de presupuestos de un periodo (mes/anio) de un workspace',
    required: ['budgets', 'total_limit', 'total_spent', 'categories_without_budget'],
    properties: [
        new OA\Property(property: 'budgets', type: 'array', items: new OA\Items(ref: '#/components/schemas/Budget')),
        new OA\Property(property: 'total_limit', type: 'number', format: 'float', example: 150000),
        new OA\Property(property: 'total_spent', type: 'number', format: 'float', example: 98000),
        new OA\Property(
            property: 'categories_without_budget',
            type: 'array',
            items: new OA\Items(
                required: ['id', 'name', 'spent_amount'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 5),
                    new OA\Property(property: 'name', type: 'string', example: 'Ocio'),
                    new OA\Property(property: 'spent_amount', type: 'number', format: 'float', example: 4200),
                ],
                type: 'object',
            ),
        ),
    ],
    type: 'object',
)]
final readonly class BudgetSummaryDto
{
    /**
     * @param BudgetDto[] $budgets
     * @param array<int, array{id:int,name:string,spent_amount:float}> $categoriesWithoutBudget
     */
    public function __construct(
        public array $budgets,
        public array $categoriesWithoutBudget,
    ) {}

    public function toArray(): array
    {
        $totalLimit = array_sum(array_map(fn (BudgetDto $b) => $b->limitAmount, $this->budgets));
        $totalSpent = array_sum(array_map(fn (BudgetDto $b) => $b->spentAmount, $this->budgets));

        return [
            'budgets' => array_map(fn (BudgetDto $b) => $b->toArray(), $this->budgets),
            'total_limit' => $totalLimit,
            'total_spent' => $totalSpent,
            'categories_without_budget' => $this->categoriesWithoutBudget,
        ];
    }
}
