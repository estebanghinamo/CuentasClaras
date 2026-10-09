<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'IncomeSummary',
    required: ['entries', 'total', 'by_user'],
    properties: [
        new OA\Property(
            property: 'entries',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/IncomeEntry'),
        ),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 300000),
        new OA\Property(
            property: 'by_user',
            type: 'array',
            items: new OA\Items(
                required: ['user_id', 'user_name', 'total'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                    new OA\Property(property: 'user_name', type: 'string', example: 'Ana Gomez'),
                    new OA\Property(property: 'total', type: 'number', format: 'float', example: 150000),
                ],
                type: 'object',
            ),
        ),
    ],
    type: 'object',
)]
final readonly class IncomeSummaryDto
{
    /**
     * @param IncomeEntryDto[] $entries
     * @param array{user_id:int,user_name:string,total:float}[] $byUser
     */
    public function __construct(
        public array $entries,
        public float $total,
        public array $byUser,
    ) {}

    public function toArray(): array
    {
        return [
            'entries' => array_map(fn (IncomeEntryDto $e) => $e->toArray(), $this->entries),
            'total' => $this->total,
            'by_user' => $this->byUser,
        ];
    }
}
