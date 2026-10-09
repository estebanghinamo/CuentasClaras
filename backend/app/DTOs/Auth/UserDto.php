<?php

namespace App\DTOs\Auth;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email', 'locale', 'theme', 'two_factor_enabled', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@example.com'),
        new OA\Property(property: 'locale', type: 'string', example: 'es'),
        new OA\Property(property: 'theme', type: 'string', enum: ['light', 'dark', 'system'], example: 'system'),
        new OA\Property(property: 'two_factor_enabled', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class UserDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $locale,
        public string $theme,
        public bool $twoFactorEnabled,
        public string $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            locale: (string) $row['locale'],
            theme: (string) $row['theme'],
            twoFactorEnabled: !empty($row['two_factor_confirmed_at']),
            createdAt: (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'theme' => $this->theme,
            'two_factor_enabled' => $this->twoFactorEnabled,
            'created_at' => $this->createdAt,
        ];
    }
}
