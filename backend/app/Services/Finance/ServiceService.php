<?php

namespace App\Services\Finance;

use App\DTOs\Finance\ServiceDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\NotFoundException;
use App\Repositories\ServiceRepository;
use App\Services\Audit\AuditSummaries;
use App\Support\Period;

class ServiceService
{
    public function __construct(private readonly ServiceRepository $services)
    {
    }

    /** @return ServiceDto[] */
    public function list(int $workspaceId, string $active = 'true'): array
    {
        return array_map(fn (array $row) => ServiceDto::fromArray($row), $this->services->list($workspaceId, $active));
    }

    public function create(WorkspaceMembershipDto $membership, array $data): ServiceDto
    {
        $period = Period::current();
        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('service', 'created', ['name' => $data['name']]),
        );

        $row = $this->services->create($membership->workspaceId, $data, $period->year, $period->month, $audit);

        return ServiceDto::fromArray($row);
    }

    public function update(WorkspaceMembershipDto $membership, int $serviceId, array $data): ServiceDto
    {
        $existing = $this->services->find($membership->workspaceId, $serviceId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('service', 'updated', ['name' => $data['name']]),
        );

        $row = $this->services->update($membership->workspaceId, $serviceId, $data, $audit);

        return ServiceDto::fromArray($row);
    }

    public function delete(WorkspaceMembershipDto $membership, int $serviceId): void
    {
        $existing = $this->services->find($membership->workspaceId, $serviceId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('service', 'deleted', ['name' => $existing['name']]),
        );

        $this->services->delete($membership->workspaceId, $serviceId, $audit);
    }

    private function auditPayload(int $userId, string $summary): array
    {
        $userAgent = request()->userAgent();

        return [
            'user_id' => $userId,
            'summary' => $summary,
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent !== null ? substr($userAgent, 0, 255) : null,
        ];
    }
}
