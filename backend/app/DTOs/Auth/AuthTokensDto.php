<?php

namespace App\DTOs\Auth;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthTokens',
    description: 'Par access/refresh token de Sanctum (ADR-002) + el usuario autenticado',
    required: ['access_token', 'access_expires_at', 'refresh_token', 'refresh_expires_at', 'user'],
    properties: [
        new OA\Property(property: 'access_token', type: 'string', example: '1|abcdef123456'),
        new OA\Property(property: 'access_expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'refresh_token', type: 'string', example: '2|ghijkl789012'),
        new OA\Property(property: 'refresh_expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User', type: 'object'),
    ],
    type: 'object',
)]
final readonly class AuthTokensDto
{
    public function __construct(
        public string $accessToken,
        public string $accessExpiresAt,
        public string $refreshToken,
        public string $refreshExpiresAt,
        public UserDto $user,
    ) {}

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'access_expires_at' => $this->accessExpiresAt,
            'refresh_token' => $this->refreshToken,
            'refresh_expires_at' => $this->refreshExpiresAt,
            'user' => $this->user->toArray(),
        ];
    }
}
