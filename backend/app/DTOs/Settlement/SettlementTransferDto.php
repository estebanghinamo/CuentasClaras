<?php

namespace App\DTOs\Settlement;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SettlementTransfer',
    description: 'Transferencia sugerida (aun no pagada) para saldar deudas entre miembros',
    required: ['from_user_id', 'from_user_name', 'to_user_id', 'to_user_name', 'amount'],
    properties: [
        new OA\Property(property: 'from_user_id', type: 'integer', example: 2),
        new OA\Property(property: 'from_user_name', type: 'string', example: 'Juan Perez'),
        new OA\Property(property: 'to_user_id', type: 'integer', example: 1),
        new OA\Property(property: 'to_user_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'amount', type: 'number', format: 'float'),
    ],
    type: 'object',
)]
final readonly class SettlementTransferDto
{
    public function __construct(
        public int $fromUserId,
        public string $fromUserName,
        public int $toUserId,
        public string $toUserName,
        public float $amount,
    ) {}

    public function toArray(): array
    {
        return [
            'from_user_id' => $this->fromUserId,
            'from_user_name' => $this->fromUserName,
            'to_user_id' => $this->toUserId,
            'to_user_name' => $this->toUserName,
            'amount' => $this->amount,
        ];
    }
}
