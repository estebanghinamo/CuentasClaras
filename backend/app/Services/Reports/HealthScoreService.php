<?php

namespace App\Services\Reports;

use App\DTOs\Reports\HealthScoreDto;
use App\Repositories\HealthScoreRepository;
use App\Support\Period;

/**
 * Puntaje 0-100 por workspace y mes (ESPECIFICACION_TECNICA.md M-15). Se
 * persiste al cerrar el mes (M-13::CloseMonthJob paso 7) y se calcula en vivo
 * para el mes en curso (M-14::DashboardService), sin persistir en ese caso.
 */
class HealthScoreService
{
    public function __construct(private readonly HealthScoreRepository $scores)
    {
    }

    /**
     * Null si no hay ingresos ni gastos en el período (nada que puntuar todavía).
     */
    public function computeLive(int $workspaceId, Period $period): ?HealthScoreDto
    {
        $inputs = $this->scores->inputs($workspaceId, $period);

        if ((float) $inputs['total_income'] === 0.0 && (float) $inputs['total_expenses'] === 0.0) {
            return null;
        }

        [$score, $breakdown] = $this->scoreFromInputs($inputs);

        return new HealthScoreDto($period->year, $period->month, $score, HealthScoreDto::levelFor($score), $breakdown);
    }

    public function computeAndStore(int $workspaceId, Period $period): HealthScoreDto
    {
        $inputs = $this->scores->inputs($workspaceId, $period);
        [$score, $breakdown] = $this->scoreFromInputs($inputs);

        $this->scores->upsert($workspaceId, $period, $score, $breakdown);

        return new HealthScoreDto($period->year, $period->month, $score, HealthScoreDto::levelFor($score), $breakdown);
    }

    /** @return HealthScoreDto[] */
    public function list(int $workspaceId, int $months): array
    {
        return array_map(
            fn (array $row) => HealthScoreDto::fromArray($row),
            $this->scores->list($workspaceId, $months),
        );
    }

    /**
     * Función pura (ESPECIFICACION_TECNICA.md M-15 §4). Pesos desde
     * config('cuentas.health_score_weights').
     *
     * @return array{0: int, 1: array} [score_total, breakdown]
     */
    public function scoreFromInputs(array $inputs): array
    {
        $weights = config('cuentas.health_score_weights');
        $totalIncome = (float) $inputs['total_income'];

        [$budgetsScore, $budgetsBreakdown] = $this->scoreBudgets($inputs['budgets'] ?? []);
        [$savingsScore, $savingsBreakdown] = $this->scoreSavings((float) $inputs['savings_in_month'], $totalIncome);
        [$servicesScore, $servicesBreakdown] = $this->scoreServices($inputs['services'] ?? []);
        [$installmentsScore, $installmentsBreakdown] = $this->scoreInstallments(
            (float) $inputs['total_installments'],
            $totalIncome,
        );

        $scoreTotal = (int) round(
            ($budgetsScore * $weights['budgets']
                + $savingsScore * $weights['savings']
                + $servicesScore * $weights['services_on_time']
                + $installmentsScore * $weights['installments_load']) / 100,
        );

        return [
            $scoreTotal,
            [
                'budgets' => ['score' => $budgetsScore, 'weight' => $weights['budgets'], ...$budgetsBreakdown],
                'savings' => ['score' => $savingsScore, 'weight' => $weights['savings'], ...$savingsBreakdown],
                'services_on_time' => [
                    'score' => $servicesScore,
                    'weight' => $weights['services_on_time'],
                    ...$servicesBreakdown,
                ],
                'installments_load' => [
                    'score' => $installmentsScore,
                    'weight' => $weights['installments_load'],
                    ...$installmentsBreakdown,
                ],
            ],
        ];
    }

    /** @return array{0: int, 1: array} */
    private function scoreBudgets(array $budgets): array
    {
        if (count($budgets) === 0) {
            return [70, ['budgets_count' => 0, 'within_limit_count' => 0, 'detail' => 'Sin presupuestos definidos']];
        }

        $ratios = array_map(
            fn (array $b) => (float) $b['limit_amount'] > 0
                ? min((float) $b['spent_amount'] / (float) $b['limit_amount'], 1.5)
                : 0.0,
            $budgets,
        );
        $avgRatio = array_sum($ratios) / count($ratios);

        $score = match (true) {
            $avgRatio <= 0.8 => 100,
            $avgRatio >= 1.3 => 0,
            default => (int) round(100 * (1.3 - $avgRatio) / 0.5),
        };

        $exceededCount = count(array_filter(
            $budgets,
            fn (array $b) => (float) $b['spent_amount'] > (float) $b['limit_amount'],
        ));
        $score = max(0, $score - $exceededCount * 10);
        $withinLimitCount = count($budgets) - $exceededCount;

        return [$score, [
            'budgets_count' => count($budgets),
            'within_limit_count' => $withinLimitCount,
            'detail' => sprintf('%d de %d presupuestos dentro del límite', $withinLimitCount, count($budgets)),
        ]];
    }

    /** @return array{0: int, 1: array} */
    private function scoreSavings(float $savingsInMonth, float $totalIncome): array
    {
        if ($totalIncome <= 0) {
            return [0, ['savings_rate_pct' => 0.0, 'detail' => 'Sin ingresos']];
        }

        $ratePct = $savingsInMonth / $totalIncome * 100;
        $score = (int) min(100, round($ratePct * 5));

        return [$score, [
            'savings_rate_pct' => round($ratePct, 1),
            'detail' => sprintf('Ahorraste %.1f%% del ingreso', $ratePct),
        ]];
    }

    /** @return array{0: int, 1: array} */
    private function scoreServices(array $services): array
    {
        $onTime = (int) ($services['paid_on_time'] ?? 0);
        $late = (int) ($services['paid_late'] ?? 0);
        $overdue = (int) ($services['overdue'] ?? 0);
        $total = $onTime + $late + $overdue;

        if ($total === 0) {
            return [100, ['paid_on_time' => 0, 'paid_late' => 0, 'overdue' => 0, 'detail' => 'Sin servicios']];
        }

        $score = (int) round(($onTime * 100 + $late * 50 + $overdue * 0) / $total);

        return [$score, [
            'paid_on_time' => $onTime,
            'paid_late' => $late,
            'overdue' => $overdue,
            'detail' => sprintf('%d a tiempo, %d con mora, %d vencidos', $onTime, $late, $overdue),
        ]];
    }

    /** @return array{0: int, 1: array} */
    private function scoreInstallments(float $totalInstallments, float $totalIncome): array
    {
        if ($totalIncome <= 0) {
            $loadPct = $totalInstallments > 0 ? 100.0 : 0.0;
        } else {
            $loadPct = $totalInstallments / $totalIncome * 100;
        }

        $score = match (true) {
            $loadPct <= 10 => 100,
            $loadPct >= 40 => 0,
            default => (int) round(100 * (40 - $loadPct) / 30),
        };

        return [$score, [
            'load_pct' => round($loadPct, 1),
            'detail' => sprintf('%.1f%% del ingreso en cuotas', $loadPct),
        ]];
    }
}
