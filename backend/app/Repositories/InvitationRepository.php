<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class InvitationRepository extends BaseRepository
{
    public function create(int $workspaceId, string $code, ?string $email, string $expiresAt, int $createdBy): array
    {
        return $this->call('sp_invitation_create', [
            'workspace_id' => $workspaceId,
            'code' => $code,
            'email' => $email,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);
    }

    public function listByWorkspace(int $workspaceId): array
    {
        return $this->call('sp_invitation_list', ['workspace_id' => $workspaceId]);
    }

    public function findByCode(string $code): ?array
    {
        try {
            return $this->call('sp_invitation_get_by_code', ['code' => $code]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function accept(string $code, int $userId, string $now): array
    {
        return $this->call('sp_invitation_accept', ['code' => $code, 'user_id' => $userId, 'now' => $now]);
    }

    public function revoke(int $workspaceId, int $invitationId): void
    {
        $this->call('sp_invitation_revoke', ['workspace_id' => $workspaceId, 'invitation_id' => $invitationId]);
    }

    public function expireStale(string $now): array
    {
        return $this->call('sp_invitation_expire_stale', ['now' => $now]);
    }

    public function listPendingByEmail(string $email): array
    {
        return $this->call('sp_invitation_list_by_email', ['email' => $email, 'now' => now()->format('Y-m-d H:i:s')]);
    }
}
