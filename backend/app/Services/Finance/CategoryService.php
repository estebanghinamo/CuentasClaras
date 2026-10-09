<?php

namespace App\Services\Finance;

use App\DTOs\Finance\CategoryDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use App\Repositories\CategoryRepository;
use App\Services\Audit\AuditSummaries;
use App\Services\Support\SpErrorMapper;

class CategoryService
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    /** @return CategoryDto[] */
    public function list(int $workspaceId): array
    {
        return array_map(fn (array $row) => CategoryDto::fromArray($row), $this->categories->list($workspaceId));
    }

    public function create(WorkspaceMembershipDto $membership, array $data): CategoryDto
    {
        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('category', 'created', ['name' => $data['name']]),
        );

        try {
            $row = $this->categories->create(
                $membership->workspaceId,
                $data['name'],
                $data['icon'],
                $data['color'],
                $audit,
            );
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e, [
                'DUPLICATE' => new ConflictException('Ya existe una categoría con ese nombre.'),
            ]);
        }

        return CategoryDto::fromArray($row);
    }

    public function update(WorkspaceMembershipDto $membership, int $categoryId, array $data): CategoryDto
    {
        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('category', 'updated', ['name' => $data['name']]),
        );

        try {
            $row = $this->categories->update(
                $membership->workspaceId,
                $categoryId,
                $data['name'],
                $data['icon'],
                $data['color'],
                $audit,
            );
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e, [
                'DUPLICATE' => new ConflictException('Ya existe una categoría con ese nombre.'),
            ]);
        }

        return CategoryDto::fromArray($row);
    }

    public function delete(WorkspaceMembershipDto $membership, int $categoryId): void
    {
        $row = $this->categories->find($membership->workspaceId, $categoryId);

        if ($row === null) {
            throw new NotFoundException();
        }

        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('category', 'deleted', ['name' => $row['name']]),
        );

        $this->categories->delete($membership->workspaceId, $categoryId, $audit);
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
