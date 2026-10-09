<?php

namespace App\DTOs\Settlement;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SettlementBalance',
    required: ['user_id', 'user_name', 'paid', 'share', 'balance'],
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'paid', type: 'number', format: 'float'),
        new OA\Property(property: 'share', type: 'number', format: 'float'),
        new OA\Property(
            property: 'balance',
            type: 'number',
            format: 'float',
            description: 'Positivo si le deben, negativo si debe',
        ),
    ],
    type: 'object',
)]
final readonly class SettlementBalanceDto
{
    public function __construct(
        public int $userId,
        public string $userName,
        public float $paid,
        public float $share,
        public float $balance,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'paid' => $this->paid,
            'share' => $this->share,
            'balance' => $this->balance,
        ];
    }
}
