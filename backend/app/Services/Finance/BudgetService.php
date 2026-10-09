<?php

namespace App\Services\Finance;

use App\DTOs\Audit\AuditEntryDto;
use App\DTOs\Finance\BudgetDto;
use App\DTOs\Finance\BudgetSummaryDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\StoredProcedureException;
use App\Repositories\BudgetRepository;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSummaries;
use App\Services\Closing\ClosingGuard;
use App\Services\Support\SpErrorMapper;
use App\Support\Period;

class BudgetService
{
    public function __construct(
        private readonly BudgetRepository $budgets,
        private readonly BudgetAlertService $budgetAlerts,
        private readonly ClosingGuard $closingGuard,
        private readonly AuditService $audit,
    ) {}

    public function summary(int $workspaceId, int $year, int $month): BudgetSummaryDto
    {
        $result = $this->budgets->list($workspaceId, $year, $month);

        return new BudgetSummaryDto(
            budgets: array_map(fn (array $row) => BudgetDto::fromArray($row), $result['budgets']),
            categoriesWithoutBudget: array_map(
                fn (array $row) => [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                    'spent_amount' => (float) $row['spent_amount'],
                ],
                $result['categories_without_budget'],
            ),
        );
    }

    /** @return array{dto:BudgetDto,created:bool} */
    public function upsert(WorkspaceMembershipDto $membership, int $userId, array $data): array
    {
        $period = Period::fromYearMonth((int) $data['year'], (int) $data['month']);
        $this->closingGuard->assertPeriodNotTooFar($period);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        try {
            $result = $this->budgets->upsert(
                $membership->workspaceId,
                (int) $data['category_id'],
                $period->year,
                $period->month,
                (string) $data['limit_amount'],
            );
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        $dto = BudgetDto::fromArray($result['budget']);

        $this->budgetAlerts->evaluate($membership->workspaceId, $dto->categoryId, $period);

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $userId,
            entityType: 'budget',
            entityId: $dto->id,
            action: $result['created'] ? 'created' : 'updated',
            summary: AuditSummaries::for(
                'budget',
                $result['created'] ? 'created' : 'updated',
                ['category' => $dto->categoryName],
            ),
            oldValue: null,
            newValue: ['category_id' => $dto->categoryId, 'limit_amount' => $dto->limitAmount],
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));

        return ['dto' => $dto, 'created' => $result['created']];
    }

    public function delete(WorkspaceMembershipDto $membership, int $userId, int $budgetId): void
    {
        try {
            $this->budgets->delete($membership->workspaceId, $budgetId);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        $this->audit->log(new AuditEntryDto(
            workspaceId: $membership->workspaceId,
            userId: $userId,
            entityType: 'budget',
            entityId: $budgetId,
            action: 'deleted',
            summary: AuditSummaries::for('budget', 'deleted'),
            oldValue: null,
            newValue: null,
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        ));
    }

    public function copyFromPeriod(WorkspaceMembershipDto $membership, array $data): int
    {
        $result = $this->budgets->copyFromPeriod(
            $membership->workspaceId,
            (int) $data['from_year'],
            (int) $data['from_month'],
            (int) $data['to_year'],
            (int) $data['to_month'],
            (bool) $data['overwrite'],
        );

        return (int) $result['copied_count'];
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
