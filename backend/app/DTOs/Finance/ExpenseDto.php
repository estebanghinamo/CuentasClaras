<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Expense',
    required: [
        'id', 'user_id', 'user_name', 'category_id', 'category_name', 'category_icon',
        'category_color', 'amount', 'description', 'payment_method', 'date', 'created_at',
        'can_edit', 'paid_by_user_id', 'paid_by_user_name',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'category_name', type: 'string', nullable: true, example: 'Supermercado'),
        new OA\Property(property: 'category_icon', type: 'string', nullable: true, example: 'shopping-cart'),
        new OA\Property(property: 'category_color', type: 'string', nullable: true, example: '#22c55e'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Super'),
        new OA\Property(
            property: 'payment_method',
            type: 'string',
            enum: ['cash', 'debit', 'credit', 'transfer', 'wallet', 'other'],
        ),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-09-01'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'can_edit', type: 'boolean', example: true),
        new OA\Property(property: 'paid_by_user_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'paid_by_user_name', type: 'string', nullable: true, example: 'Ana Gomez'),
    ],
    type: 'object',
)]
final readonly class ExpenseDto
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $userName,
        public ?int $categoryId,
        public ?string $categoryName,
        public ?string $categoryIcon,
        public ?string $categoryColor,
        public float $amount,
        public ?string $description,
        public string $paymentMethod,
        public string $date,
        public string $createdAt,
        public bool $canEdit,
        public ?int $paidByUserId,
        public ?string $paidByUserName,
    ) {}

    public static function fromArray(array $row, bool $canEdit): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            userName: (string) $row['user_name'],
            categoryId: isset($row['category_id']) ? (int) $row['category_id'] : null,
            categoryName: $row['category_name'] ?? null,
            categoryIcon: $row['category_icon'] ?? null,
            categoryColor: $row['category_color'] ?? null,
            amount: (float) $row['amount'],
            description: $row['description'] ?? null,
            paymentMethod: (string) $row['payment_method'],
            date: (string) $row['date'],
            createdAt: (string) $row['created_at'],
            canEdit: $canEdit,
            paidByUserId: isset($row['paid_by_user_id']) ? (int) $row['paid_by_user_id'] : null,
            paidByUserName: $row['paid_by_user_name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'category_icon' => $this->categoryIcon,
            'category_color' => $this->categoryColor,
            'amount' => $this->amount,
            'description' => $this->description,
            'payment_method' => $this->paymentMethod,
            'date' => $this->date,
            'created_at' => $this->createdAt,
            'can_edit' => $this->canEdit,
            'paid_by_user_id' => $this->paidByUserId,
            'paid_by_user_name' => $this->paidByUserName,
        ];
    }
}
