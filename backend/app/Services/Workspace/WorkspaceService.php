<?php

namespace App\Services\Workspace;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Workspace\WorkspaceDto;
use App\DTOs\Workspace\WorkspaceMemberDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\InvalidStateException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use App\Repositories\InvitationRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSummaries;
use App\Services\Support\SpErrorMapper;

class WorkspaceService
{
    public function __construct(
        private readonly WorkspaceRepository $workspaces,
        private readonly InvitationRepository $invitations,
        private readonly AuditService $audit,
    ) {}

    /** @return WorkspaceDto[] */
    public function listForUser(int $userId): array
    {
        return array_map(fn (array $row) => WorkspaceDto::fromArray($row), $this->workspaces->listByUser($userId));
    }

    public function create(int $userId, array $data): WorkspaceDto
    {
        $row = $this->workspaces->create($data['name'], $data['currency'], $data['type'], $userId);

        $dto = WorkspaceDto::fromArray($row);

        $this->audit->log(new AuditEntryDto(
            workspaceId: $dto->id,
            userId: $userId,
            entityType: 'workspace',
            entityId: $dto->id,
            action: 'created',
            summary: AuditSummaries::for('workspace', 'created', ['name' => $dto->name]),
            oldValue: null,
            newValue: ['name' => $dto->name, 'currency' => $dto->currency, 'type' => $dto->type],
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        return $dto;
    }

    public function get(int $workspaceId, int $userId): WorkspaceDto
    {
        $row = $this->workspaces->find($workspaceId, $userId);

        if ($row === null) {
            throw new NotFoundException();
        }

        return WorkspaceDto::fromArray($row);
    }

    public function update(WorkspaceMembershipDto $membership, array $data): WorkspaceDto
    {
        try {
            $row = $this->workspaces->update($membership->workspaceId, $data['name'], $data['currency'], $data['type']);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e, [
                'INVALID_STATE' => new InvalidStateException('No se puede pasar a individual con más de un miembro.'),
            ]);
        }

        if ($data['type'] === 'individual' && $membership->workspaceType !== 'individual') {
            $this->revokePendingInvitations($membership->workspaceId, $membership->userId);
        }

        $row['role'] = $membership->role;
        $dto = WorkspaceDto::fromArray($row);

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'workspace',
            entityId: $membership->workspaceId,
            action: 'updated',
            summary: AuditSummaries::for('workspace', 'updated', ['name' => $dto->name]),
            oldValue: [
                'name' => $membership->workspaceName,
                'currency' => $membership->workspaceCurrency,
                'type' => $membership->workspaceType,
            ],
            newValue: ['name' => $dto->name, 'currency' => $dto->currency, 'type' => $dto->type],
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        return $dto;
    }

    public function delete(WorkspaceMembershipDto $membership): void
    {
        // Auditoría antes del delete: el FK cascade borra audit_logs del workspace al eliminarlo.
        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'workspace',
            entityId: $membership->workspaceId,
            action: 'deleted',
            summary: AuditSummaries::for('workspace', 'deleted', ['name' => $membership->workspaceName]),
            oldValue: [
                'name' => $membership->workspaceName,
                'currency' => $membership->workspaceCurrency,
                'type' => $membership->workspaceType,
            ],
            newValue: null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        $this->workspaces->delete($membership->workspaceId);
    }

    /** @return WorkspaceMemberDto[] */
    public function listMembers(int $workspaceId): array
    {
        return array_map(
            fn (array $row) => WorkspaceMemberDto::fromArray($row),
            $this->workspaces->listMembers($workspaceId),
        );
    }

    public function removeMember(WorkspaceMembershipDto $membership, int $targetUserId): void
    {
        $target = $this->findMember($membership->workspaceId, $targetUserId);

        try {
            $this->workspaces->removeMember($membership->workspaceId, $targetUserId);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e, [
                'INVALID_STATE' => new InvalidStateException(
                    'El propietario no puede abandonar el workspace; transferilo o eliminalo.',
                ),
            ]);
        }

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $membership->userId,
            entityType: 'workspace_member',
            entityId: $targetUserId,
            action: 'deleted',
            summary: AuditSummaries::for('workspace_member', 'deleted', [
                'self' => $membership->userId === $targetUserId,
                'workspace_name' => $membership->workspaceName,
                'name' => $target->name,
            ]),
            oldValue: ['user_id' => $target->userId, 'email' => $target->email, 'role' => $target->role],
            newValue: null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));
    }

    public function markOnboarding(WorkspaceMembershipDto $membership): WorkspaceDto
    {
        $row = $this->workspaces->markOnboarding($membership->workspaceId);
        $row['role'] = $membership->role;

        return WorkspaceDto::fromArray($row);
    }

    private function findMember(int $workspaceId, int $userId): WorkspaceMemberDto
    {
        foreach ($this->workspaces->listMembers($workspaceId) as $row) {
            if ((int) $row['user_id'] === $userId) {
                return WorkspaceMemberDto::fromArray($row);
            }
        }

        throw new NotFoundException();
    }

    private function revokePendingInvitations(int $workspaceId, int $userId): void
    {
        foreach ($this->invitations->listByWorkspace($workspaceId) as $row) {
            if ($row['status'] !== 'pending') {
                continue;
            }

            $this->invitations->revoke($workspaceId, (int) $row['id']);

            $this->audit->log(new AuditEntryDto(
                workspaceId: $workspaceId,
                userId: $userId,
                entityType: 'workspace_invitation',
                entityId: (int) $row['id'],
                action: 'deleted',
                summary: AuditSummaries::for('workspace_invitation', 'deleted'),
                oldValue: ['code' => $row['code'], 'status' => 'pending'],
                newValue: null,
                ipAddress: $this->ip(),
                userAgent: $this->userAgent(),
            ));
        }
    }

    private function ip(): ?string
    {
        return request()->ip();
    }

    private function userAgent(): ?string
    {
        $agent = request()->userAgent();

        return $agent !== null ? substr($agent, 0, 255) : null;
    }
}
