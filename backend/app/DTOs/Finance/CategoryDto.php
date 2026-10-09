<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Category',
    description: 'Categoria de gastos de un workspace (por defecto o personalizada)',
    required: ['id', 'name', 'icon', 'color', 'is_default', 'expenses_count'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Comida'),
        new OA\Property(property: 'icon', type: 'string', example: 'food'),
        new OA\Property(property: 'color', type: 'string', example: '#FF5733'),
        new OA\Property(property: 'is_default', type: 'boolean', example: false),
        new OA\Property(property: 'expenses_count', type: 'integer', example: 12),
    ],
    type: 'object',
)]
final readonly class CategoryDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $icon,
        public string $color,
        public bool $isDefault,
        public int $expensesCount,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            icon: (string) $row['icon'],
            color: (string) $row['color'],
            isDefault: !empty($row['is_default']),
            expensesCount: (int) ($row['expenses_count'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_default' => $this->isDefault,
            'expenses_count' => $this->expensesCount,
        ];
    }
}
