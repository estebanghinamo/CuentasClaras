<?php

namespace App\DTOs\Workspace;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Invitation',
    required: ['id', 'code', 'link', 'email', 'status', 'expires_at', 'created_at', 'created_by_name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'a1b2c3d4'),
        new OA\Property(
            property: 'link',
            type: 'string',
            example: 'https://app.cuentasclaras.com/invitations/a1b2c3d4',
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            nullable: true,
            description: 'Restringe la invitacion a este email si esta presente',
        ),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'accepted', 'revoked', 'expired']),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_by_name', type: 'string', example: 'Ana Gomez'),
    ],
    type: 'object',
)]
final readonly class InvitationDto
{
    public function __construct(
        public int $id,
        public string $code,
        public string $link,
        public ?string $email,
        public string $status,
        public string $expiresAt,
        public string $createdAt,
        public string $createdByName,
    ) {}

    public static function fromArray(array $row, string $link): self
    {
        return new self(
            id: (int) $row['id'],
            code: (string) $row['code'],
            link: $link,
            email: $row['email'] !== null ? (string) $row['email'] : null,
            status: (string) $row['status'],
            expiresAt: (string) $row['expires_at'],
            createdAt: (string) $row['created_at'],
            createdByName: (string) $row['created_by_name'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'link' => $this->link,
            'email' => $this->email,
            'status' => $this->status,
            'expires_at' => $this->expiresAt,
            'created_at' => $this->createdAt,
            'created_by_name' => $this->createdByName,
        ];
    }
}
