<?php

namespace App\Services\Workspace;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\ForbiddenException;

/**
 * Reglas de permisos por tipo de workspace (ESPECIFICACION_TECNICA.md §0.9).
 * Métodos puros, sin I/O: se usan desde los Services de cada módulo que maneja
 * "registros personales" (income_entries, expenses, installments/installment_payments).
 */
class WorkspacePermissions
{
    public function canEditPersonalRecord(WorkspaceMembershipDto $membership, int $recordOwnerUserId): bool
    {
        return match ($membership->workspaceType) {
            'individual', 'shared_joint', 'shared_settlement' => true,
            'shared_separate' => $membership->userId === $recordOwnerUserId,
            default => false,
        };
    }

    public function assertCanEditPersonalRecord(WorkspaceMembershipDto $membership, int $recordOwnerUserId): void
    {
        if (!$this->canEditPersonalRecord($membership, $recordOwnerUserId)) {
            throw new ForbiddenException('No podés modificar registros de otro miembro.');
        }
    }
}
