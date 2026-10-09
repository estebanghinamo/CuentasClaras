<?php

namespace App\Services\Workspace;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\NotFoundException;
use App\Repositories\WorkspaceRepository;

class WorkspaceAccessService
{
    public function __construct(private readonly WorkspaceRepository $workspaces)
    {
    }

    public function getMembership(int $workspaceId, int $userId): WorkspaceMembershipDto
    {
        $row = $this->workspaces->getMembership($workspaceId, $userId);

        if ($row === null) {
            throw new NotFoundException();
        }

        return WorkspaceMembershipDto::fromArray($row);
    }
}
