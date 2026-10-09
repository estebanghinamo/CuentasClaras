<?php

namespace App\DTOs\Settlement;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SettlementPayment',
    description: 'Pago ya registrado que salda (total o parcialmente) una deuda entre miembros',
    required: [
        'id', 'from_user_id', 'from_user_name', 'to_user_id', 'to_user_name', 'amount', 'note',
        'registered_by', 'registered_by_name', 'paid_at', 'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'from_user_id', type: 'integer', example: 2),
        new OA\Property(property: 'from_user_name', type: 'string', example: 'Juan Perez'),
        new OA\Property(property: 'to_user_id', type: 'integer', example: 1),
        new OA\Property(property: 'to_user_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'amount', type: 'number', format: 'float'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'registered_by', type: 'integer', example: 1),
        new OA\Property(property: 'registered_by_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class SettlementPaymentDto
{
    public function __construct(
        public int $id,
        public int $fromUserId,
        public string $fromUserName,
        public int $toUserId,
        public string $toUserName,
        public float $amount,
        public ?string $note,
        public int $registeredBy,
        public string $registeredByName,
        public string $paidAt,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            fromUserId: (int) $row['from_user_id'],
            fromUserName: (string) $row['from_user_name'],
            toUserId: (int) $row['to_user_id'],
            toUserName: (string) $row['to_user_name'],
            amount: (float) $row['amount'],
            note: $row['note'] ?? null,
            registeredBy: (int) $row['registered_by'],
            registeredByName: (string) $row['registered_by_name'],
            paidAt: (string) $row['paid_at'],
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'from_user_id' => $this->fromUserId,
            'from_user_name' => $this->fromUserName,
            'to_user_id' => $this->toUserId,
            'to_user_name' => $this->toUserName,
            'amount' => $this->amount,
            'note' => $this->note,
            'registered_by' => $this->registeredBy,
            'registered_by_name' => $this->registeredByName,
            'paid_at' => $this->paidAt,
            'created_at' => $this->createdAt,
        ];
    }
}
