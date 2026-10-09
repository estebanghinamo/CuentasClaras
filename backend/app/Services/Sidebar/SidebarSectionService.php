<?php

namespace App\Services\Sidebar;

use App\DTOs\Sidebar\SidebarSectionsDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Repositories\SidebarSectionRepository;

class SidebarSectionService
{
    public function __construct(private readonly SidebarSectionRepository $sections)
    {
    }

    public function list(WorkspaceMembershipDto $membership): SidebarSectionsDto
    {
        $row = $this->sections->list($membership->workspaceId, $membership->userId);

        return SidebarSectionsDto::fromArray($row);
    }

    public function set(WorkspaceMembershipDto $membership, array $sections): SidebarSectionsDto
    {
        $row = $this->sections->set($membership->workspaceId, $membership->userId, $sections);

        return SidebarSectionsDto::fromArray($row);
    }
}
