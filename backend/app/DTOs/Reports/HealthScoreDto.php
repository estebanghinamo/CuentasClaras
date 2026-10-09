<?php

namespace App\DTOs\Reports;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'HealthScore',
    required: ['year', 'month', 'score', 'level', 'breakdown'],
    properties: [
        new OA\Property(property: 'year', type: 'integer', example: 2026),
        new OA\Property(property: 'month', type: 'integer', example: 9),
        new OA\Property(property: 'score', type: 'integer', minimum: 0, maximum: 100, example: 75),
        new OA\Property(property: 'level', type: 'string', enum: ['good', 'warning', 'bad'], example: 'good'),
        new OA\Property(property: 'breakdown', type: 'object', additionalProperties: true),
    ],
    type: 'object',
)]
final readonly class HealthScoreDto
{
    public function __construct(
        public int $year,
        public int $month,
        public int $score,
        public string $level,
        public array $breakdown,
    ) {}

    public static function fromArray(array $row): self
    {
        $score = (int) $row['score'];

        return new self(
            year: (int) $row['year'],
            month: (int) $row['month'],
            score: $score,
            level: self::levelFor($score),
            breakdown: $row['breakdown'] ?? [],
        );
    }

    public static function levelFor(int $score): string
    {
        return match (true) {
            $score >= 70 => 'good',
            $score >= 40 => 'warning',
            default => 'bad',
        };
    }

    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'score' => $this->score,
            'level' => $this->level,
            'breakdown' => $this->breakdown,
        ];
    }
}
