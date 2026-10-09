<?php

namespace App\DTOs\Workspace;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InvitationPreview',
    description: 'Vista previa publica de una invitacion (sin autenticacion)',
    required: ['code', 'workspace_name', 'workspace_type', 'invited_by_name', 'email_restricted', 'expires_at'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'a1b2c3d4'),
        new OA\Property(property: 'workspace_name', type: 'string', example: 'Finanzas del hogar'),
        new OA\Property(
            property: 'workspace_type',
            type: 'string',
            enum: ['individual', 'shared_joint', 'shared_separate', 'shared_settlement'],
        ),
        new OA\Property(property: 'invited_by_name', type: 'string', example: 'Ana Gomez'),
        new OA\Property(
            property: 'email_restricted',
            type: 'boolean',
            description: 'true si la invitacion solo puede ser aceptada por un email especifico',
        ),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
final readonly class InvitationPreviewDto
{
    public function __construct(
        public string $code,
        public string $workspaceName,
        public string $workspaceType,
        public string $invitedByName,
        public bool $emailRestricted,
        public string $expiresAt,
    ) {}

    public static function fromArray(array $row, string $code): self
    {
        return new self(
            code: $code,
            workspaceName: (string) $row['workspace_name'],
            workspaceType: (string) $row['workspace_type'],
            invitedByName: (string) $row['created_by_name'],
            emailRestricted: !empty($row['email']),
            expiresAt: (string) $row['expires_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'workspace_name' => $this->workspaceName,
            'workspace_type' => $this->workspaceType,
            'invited_by_name' => $this->invitedByName,
            'email_restricted' => $this->emailRestricted,
            'expires_at' => $this->expiresAt,
        ];
    }
}
