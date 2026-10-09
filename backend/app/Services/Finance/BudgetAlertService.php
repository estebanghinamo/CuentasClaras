<?php

namespace App\Services\Finance;

use App\Exceptions\StoredProcedureException;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\Notifications\NotificationDispatcher;
use App\Support\Period;

/**
 * Evaluación de alertas de presupuesto (ESPECIFICACION_TECNICA.md M-12 §4).
 * Se llama desde ExpenseService (create/update/delete) y desde BudgetService::upsert.
 *
 * Notificación conectada (M-17, 2026-09-23): si el nivel SUBE (none < warning
 * < reached < exceeded) se avisa a todos los miembros vía NotificationDispatcher
 * (respeta budget_alert_levels/muted_types de cada uno). Si el nivel baja
 * (se borró un gasto) solo se persiste, sin notificar - un presupuesto que
 * mejora no es una alerta.
 */
class BudgetAlertService
{
    private const LEVEL_ORDER = ['none' => 0, 'warning' => 1, 'reached' => 2, 'exceeded' => 3];

    public function __construct(
        private readonly BudgetRepository $budgets,
        private readonly CategoryRepository $categories,
        private readonly WorkspaceRepository $workspaces,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function evaluate(int $workspaceId, ?int $categoryId, Period $period): void
    {
        if ($categoryId === null) {
            return;
        }

        try {
            $progress = $this->budgets->progress($workspaceId, $categoryId, $period->year, $period->month);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return;
            }

            throw $e;
        }

        $limit = (float) $progress['limit_amount'];
        $spent = (float) $progress['spent_amount'];
        $pct = $limit > 0 ? ($spent / $limit * 100) : 0.0;

        $newLevel = match (true) {
            $pct > 100 => 'exceeded',
            $pct >= 100 => 'reached',
            $pct >= 80 => 'warning',
            default => 'none',
        };
        $previousLevel = (string) $progress['alert_level'];

        if ($newLevel === $previousLevel) {
            return;
        }

        $this->budgets->updateAlertLevel($workspaceId, (int) $progress['budget_id'], $newLevel);

        if (self::LEVEL_ORDER[$newLevel] > self::LEVEL_ORDER[$previousLevel]) {
            $this->notifyMembers($workspaceId, $categoryId, $period, $newLevel, $limit, $spent, $pct);
        }
    }

    private function notifyMembers(
        int $workspaceId,
        int $categoryId,
        Period $period,
        string $level,
        float $limit,
        float $spent,
        float $pct,
    ): void {
        $category = $this->categories->find($workspaceId, $categoryId);
        $categoryName = $category['name'] ?? 'una categoría';

        $memberIds = array_map(
            fn (array $m) => (int) $m['user_id'],
            $this->workspaces->listMembers($workspaceId),
        );

        $levelText = match ($level) {
            'warning' => 'llegó al 80%',
            'reached' => 'llegó al límite',
            'exceeded' => 'superó el límite',
            default => 'cambió',
        };

        $this->dispatcher->notifyUsers(
            $memberIds,
            'budget_alert',
            'Presupuesto de '.$categoryName,
            "El presupuesto de {$categoryName} {$levelText} este mes ("
                .round($pct).'% de $'.number_format($limit, 2).').',
            '/w/'.$workspaceId.'/budgets',
            $workspaceId,
            [
                'budget_id' => $categoryId, 'category_id' => $categoryId, 'category_name' => $categoryName,
                'year' => $period->year, 'month' => $period->month,
                'limit_amount' => $limit, 'spent_amount' => $spent, 'pct' => round($pct, 1), 'level' => $level,
            ],
        );
    }
}
