<?php

namespace App\Services\Savings;

use App\DTOs\Savings\SavingsGoalDto;
use App\DTOs\Savings\SavingsGoalMovementDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\Audit\AuditSummaries;
use App\Services\Closing\ClosingGuard;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reports\DashboardService;
use App\Services\Support\SpErrorMapper;
use App\Support\Period;
use Carbon\CarbonImmutable;

/**
 * Igual que el monedero (M-10), las metas de ahorro son entidad COMPARTIDA
 * del workspace - sin chequeo de WorkspacePermissions::canEditPersonalRecord.
 */
class SavingsGoalService
{
    public function __construct(
        private readonly SavingsGoalRepository $goals,
        private readonly ClosingGuard $closingGuard,
        private readonly DashboardService $dashboard,
        private readonly NotificationDispatcher $dispatcher,
        private readonly WorkspaceRepository $workspaces,
    ) {}

    /** @return SavingsGoalDto[] */
    public function list(WorkspaceMembershipDto $membership, string $status = 'active'): array
    {
        return array_map(fn (array $row) => $this->toDto($row), $this->goals->list($membership->workspaceId, $status));
    }

    public function get(WorkspaceMembershipDto $membership, int $goalId): SavingsGoalDto
    {
        $row = $this->goals->find($membership->workspaceId, $goalId);

        if ($row === null) {
            throw new NotFoundException();
        }

        return $this->toDto($row);
    }

    public function create(WorkspaceMembershipDto $membership, array $data, int $userId): SavingsGoalDto
    {
        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('savings_goal', 'created', ['name' => $data['name']]),
        );
        $row = $this->goals->create($membership->workspaceId, $data, $audit);

        return $this->toDto($row);
    }

    public function update(WorkspaceMembershipDto $membership, int $goalId, array $data, int $userId): SavingsGoalDto
    {
        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('savings_goal', 'updated', ['name' => $data['name']]),
        );

        try {
            $row = $this->goals->update($membership->workspaceId, $goalId, $data, $audit);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return $this->toDto($row);
    }

    public function cancel(WorkspaceMembershipDto $membership, int $goalId, int $userId): void
    {
        $existing = $this->get($membership, $goalId);
        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('savings_goal', 'deleted', ['name' => $existing->name]),
        );
        $this->goals->cancel($membership->workspaceId, $goalId, $audit);
    }

    public function contribute(
        WorkspaceMembershipDto $membership,
        int $goalId,
        string $type,
        float $amount,
        ?string $note,
        int $userId,
    ): SavingsGoalDto {
        $existing = $this->get($membership, $goalId);
        $period = Period::current();
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        if ($type === 'contribution' && $amount > $this->dashboard->availableForPeriod($membership, $period)) {
            throw new InsufficientFundsException('No podés aportar más de tu disponible del mes.');
        }

        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('savings_goal', 'updated', [
                'name' => $existing->name,
                'amount' => $amount,
                'reason' => $type === 'contribution' ? 'contribution' : 'withdrawal',
            ]),
        );

        try {
            $row = $this->goals->contribute($membership->workspaceId, $goalId, [
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'source' => 'manual',
                'closing_id' => null,
                'note' => $note,
            ], $audit);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        $dto = $this->toDto($row);

        if ($existing->status !== 'completed' && $dto->status === 'completed') {
            $this->notifyGoalCompleted($membership->workspaceId, $dto);
        }

        return $dto;
    }

    /** M-17 (2026-09-23): avisa a todos los miembros cuando un aporte manual completa la meta. */
    private function notifyGoalCompleted(int $workspaceId, SavingsGoalDto $goal): void
    {
        $memberIds = array_map(fn (array $m) => (int) $m['user_id'], $this->workspaces->listMembers($workspaceId));

        $this->dispatcher->notifyUsers(
            $memberIds,
            'savings_goal_completed',
            '¡Meta completada!',
            "Llegaron a la meta \"{$goal->name}\" (\${$goal->targetAmount}).",
            '/w/'.$workspaceId.'/goals',
            $workspaceId,
            ['goal_id' => $goal->id, 'goal_name' => $goal->name, 'target_amount' => $goal->targetAmount],
        );
    }

    /** @return SavingsGoalMovementDto[] */
    public function movements(WorkspaceMembershipDto $membership, int $goalId): array
    {
        return array_map(
            fn (array $row) => SavingsGoalMovementDto::fromArray($row),
            $this->goals->movements($membership->workspaceId, $goalId),
        );
    }

    /** Atajo de un paso (P-16): retira del monedero y aporta a la meta en una sola transacción. */
    public function transferFromWallet(
        WorkspaceMembershipDto $membership,
        int $goalId,
        float $amount,
        ?string $note,
        int $userId,
    ): array {
        $existing = $this->get($membership, $goalId);
        $period = Period::current();
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('savings_goal', 'updated', [
                'name' => $existing->name,
                'amount' => $amount,
                'reason' => 'transfer',
            ]),
        );

        try {
            $row = $this->goals->transferFromWallet($membership->workspaceId, $goalId, [
                'user_id' => $userId,
                'amount' => $amount,
                'year' => $period->year,
                'month' => $period->month,
                'note' => $note,
            ], $audit);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return [
            'wallet_balance' => (float) $row['wallet_balance'],
            'goal' => $this->toDto($row['goal']),
        ];
    }

    private function toDto(array $row): SavingsGoalDto
    {
        $target = (float) $row['target_amount'];
        $current = (float) $row['current_amount'];
        $dueDate = $row['due_date'] ?? null;
        $status = (string) $row['status'];

        $remaining = max(0.0, $target - $current);
        $progressPct = $target > 0 ? round(min(100, $current / $target * 100), 2) : 0.0;

        $daysLeft = null;
        $suggestedMonthly = null;

        if ($dueDate !== null) {
            $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
            $due = CarbonImmutable::createFromFormat('Y-m-d', $dueDate, config('app.timezone'))->startOfDay();
            $daysLeft = (int) $today->diffInDays($due, false);

            if ($status === 'active') {
                $monthsLeft = max(1, (int) ceil($today->diffInDays($due, false) / 30));
                $suggestedMonthly = round($remaining / $monthsLeft, 2);
            }
        }

        return new SavingsGoalDto(
            id: (int) $row['id'],
            name: (string) $row['name'],
            targetAmount: $target,
            currentAmount: $current,
            dueDate: $dueDate,
            status: $status,
            progressPct: $progressPct,
            remainingAmount: $remaining,
            daysLeft: $daysLeft,
            suggestedMonthly: $suggestedMonthly,
            completedAt: $row['completed_at'] ?? null,
            createdAt: (string) $row['created_at'],
        );
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
