<?php

namespace App\DTOs\Savings;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SavingsWallet',
    description: 'Billetera de ahorro del workspace (una por workspace)',
    required: ['id', 'balance', 'total_deposited', 'total_withdrawn', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 15000.50),
        new OA\Property(property: 'total_deposited', type: 'number', format: 'float', example: 20000),
        new OA\Property(property: 'total_withdrawn', type: 'number', format: 'float', example: 4999.50),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class SavingsWalletDto
{
    public function __construct(
        public int $id,
        public float $balance,
        public float $totalDeposited,
        public float $totalWithdrawn,
        public string $updatedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            balance: (float) $row['balance'],
            totalDeposited: (float) $row['total_deposited'],
            totalWithdrawn: (float) $row['total_withdrawn'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'balance' => $this->balance,
            'total_deposited' => $this->totalDeposited,
            'total_withdrawn' => $this->totalWithdrawn,
            'updated_at' => $this->updatedAt,
        ];
    }
}
