<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Budget',
    description: 'Presupuesto mensual de una categoria (incluye calculos derivados)',
    required: [
        'id', 'category_id', 'category_name', 'category_icon', 'category_color', 'year', 'month',
        'limit_amount', 'spent_amount', 'remaining_amount', 'progress_pct', 'alert_level',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'category_id', type: 'integer', example: 3),
        new OA\Property(property: 'category_name', type: 'string', example: 'Comida'),
        new OA\Property(property: 'category_icon', type: 'string', example: 'food'),
        new OA\Property(property: 'category_color', type: 'string', example: '#FF5733'),
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', example: 9),
        new OA\Property(property: 'limit_amount', type: 'number', format: 'float', example: 50000),
        new OA\Property(property: 'spent_amount', type: 'number', format: 'float', example: 32000),
        new OA\Property(property: 'remaining_amount', type: 'number', format: 'float', example: 18000),
        new OA\Property(property: 'progress_pct', type: 'number', format: 'float', example: 64.0),
        new OA\Property(property: 'alert_level', type: 'string', enum: ['ok', 'warning', 'exceeded']),
    ],
    type: 'object',
)]
final readonly class BudgetDto
{
    public function __construct(
        public int $id,
        public int $categoryId,
        public string $categoryName,
        public string $categoryIcon,
        public string $categoryColor,
        public int $year,
        public int $month,
        public float $limitAmount,
        public float $spentAmount,
        public string $alertLevel,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            categoryId: (int) $row['category_id'],
            categoryName: (string) $row['category_name'],
            categoryIcon: (string) $row['category_icon'],
            categoryColor: (string) $row['category_color'],
            year: (int) $row['year'],
            month: (int) $row['month'],
            limitAmount: (float) $row['limit_amount'],
            spentAmount: (float) $row['spent_amount'],
            alertLevel: (string) $row['alert_level'],
        );
    }

    public function toArray(): array
    {
        $remaining = $this->limitAmount - $this->spentAmount;

        return [
            'id' => $this->id,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'category_icon' => $this->categoryIcon,
            'category_color' => $this->categoryColor,
            'year' => $this->year,
            'month' => $this->month,
            'limit_amount' => $this->limitAmount,
            'spent_amount' => $this->spentAmount,
            'remaining_amount' => $remaining,
            'progress_pct' => $this->limitAmount > 0 ? round($this->spentAmount / $this->limitAmount * 100, 1) : 0.0,
            'alert_level' => $this->alertLevel,
        ];
    }
}
