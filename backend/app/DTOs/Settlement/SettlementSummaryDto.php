<?php

namespace App\DTOs\Settlement;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SettlementSummary',
    required: [
        'total_expenses', 'member_count', 'share_per_person', 'balances',
        'pending_settlements', 'settled_payments',
    ],
    properties: [
        new OA\Property(property: 'total_expenses', type: 'number', format: 'float'),
        new OA\Property(property: 'member_count', type: 'integer', example: 2),
        new OA\Property(property: 'share_per_person', type: 'number', format: 'float'),
        new OA\Property(
            property: 'balances',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SettlementBalance'),
        ),
        new OA\Property(
            property: 'pending_settlements',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SettlementTransfer'),
        ),
        new OA\Property(
            property: 'settled_payments',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SettlementPayment'),
        ),
    ],
    type: 'object',
)]
final readonly class SettlementSummaryDto
{
    /**
     * @param SettlementBalanceDto[] $balances
     * @param SettlementTransferDto[] $pendingSettlements
     * @param SettlementPaymentDto[] $settledPayments
     */
    public function __construct(
        public float $totalExpenses,
        public int $memberCount,
        public float $sharePerPerson,
        public array $balances,
        public array $pendingSettlements,
        public array $settledPayments,
    ) {}

    public function toArray(): array
    {
        return [
            'total_expenses' => $this->totalExpenses,
            'member_count' => $this->memberCount,
            'share_per_person' => $this->sharePerPerson,
            'balances' => array_map(fn (SettlementBalanceDto $b) => $b->toArray(), $this->balances),
            'pending_settlements' => array_map(
                fn (SettlementTransferDto $t) => $t->toArray(),
                $this->pendingSettlements,
            ),
            'settled_payments' => array_map(fn (SettlementPaymentDto $p) => $p->toArray(), $this->settledPayments),
        ];
    }
}
