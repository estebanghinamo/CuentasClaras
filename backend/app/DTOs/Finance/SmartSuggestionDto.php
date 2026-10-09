<?php

namespace App\DTOs\Finance;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SmartSuggestion',
    required: [
        'id', 'description', 'avg_amount', 'frequency_detected', 'occurrences', 'last_seen_date',
        'suggested_due_day', 'status', 'service_id', 'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'description', type: 'string', example: 'Netflix'),
        new OA\Property(property: 'avg_amount', type: 'number', format: 'float'),
        new OA\Property(property: 'frequency_detected', type: 'string', example: 'monthly'),
        new OA\Property(property: 'occurrences', type: 'integer', example: 3),
        new OA\Property(property: 'last_seen_date', type: 'string', format: 'date'),
        new OA\Property(property: 'suggested_due_day', type: 'integer', minimum: 1, maximum: 31),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'accepted', 'dismissed']),
        new OA\Property(property: 'service_id', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class SmartSuggestionDto
{
    public function __construct(
        public int $id,
        public string $description,
        public float $avgAmount,
        public string $frequencyDetected,
        public int $occurrences,
        public string $lastSeenDate,
        public int $suggestedDueDay,
        public string $status,
        public ?int $serviceId,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            description: (string) $row['description'],
            avgAmount: (float) $row['avg_amount'],
            frequencyDetected: (string) $row['frequency_detected'],
            occurrences: (int) $row['occurrences'],
            lastSeenDate: (string) $row['last_seen_date'],
            suggestedDueDay: (int) $row['suggested_due_day'],
            status: (string) $row['status'],
            serviceId: isset($row['service_id']) ? (int) $row['service_id'] : null,
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'avg_amount' => $this->avgAmount,
            'frequency_detected' => $this->frequencyDetected,
            'occurrences' => $this->occurrences,
            'last_seen_date' => $this->lastSeenDate,
            'suggested_due_day' => $this->suggestedDueDay,
            'status' => $this->status,
            'service_id' => $this->serviceId,
            'created_at' => $this->createdAt,
        ];
    }
}
