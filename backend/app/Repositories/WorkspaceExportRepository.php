<?php

namespace App\Repositories;

class WorkspaceExportRepository extends BaseRepository
{
    /** @return array{entries: array<int, array<string, mixed>>} */
    public function incomeEntries(int $workspaceId): array
    {
        return $this->call('sp_income_entry_list_all', ['workspace_id' => $workspaceId]);
    }

    /** @return array{items: array<int, array<string, mixed>>} */
    public function servicePayments(int $workspaceId): array
    {
        return $this->call('sp_service_payment_list_all', ['workspace_id' => $workspaceId]);
    }
}
