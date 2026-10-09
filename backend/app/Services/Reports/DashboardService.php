<?php

namespace App\Services\Reports;

use App\DTOs\Closing\MonthlyClosingDto;
use App\DTOs\Reports\DashboardDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Repositories\DashboardRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\MonthlyClosingRepository;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\SavingsWalletRepository;
use App\Repositories\ServicePaymentRepository;
use App\Support\Period;
use Carbon\CarbonImmutable;

/**
 * Versión reducida del dashboard (ver ESPECIFICACION_TECNICA.md M-14): sin
 * cuotas (M-09), sin presupuestos (M-12), sin cierre mensual (M-13, así que
 * siempre calcula en vivo, nunca lee una "foto" congelada), sin health score
 * (M-15) ni proyección. Incorpora servicios vencidos y pagos de servicios al
 * resumen mensual, sin contar servicios pendientes o vencidos como gasto.
 *
 * P-15 (decisión del usuario, 2026-09-19): un depósito manual al monedero SÍ
 * resta del "disponible" del mes (y un retiro lo devuelve) - se usa el NETO
 * (depositado - retirado) del período. Distinto del default "no" que traía
 * ESPECIFICACION_TECNICA.md M-10 §4. Extendido igual a M-11 Metas de ahorro:
 * un aporte manual a una meta TAMBIÉN resta (neto aportado - retirado). Una
 * transferencia monedero->meta (P-16) pasa por AMBOS conteos (retiro de
 * wallet + aporte a meta) y se cancela sola matemáticamente - no hay doble
 * descuento, mover plata entre "cajones" de ahorro no cambia cuánto ingreso
 * del mes sigue sin asignar.
 */
class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboard,
        private readonly ServicePaymentRepository $servicePayments,
        private readonly InstallmentRepository $installments,
        private readonly SavingsWalletRepository $savingsWallet,
        private readonly SavingsGoalRepository $savingsGoals,
        private readonly HealthScoreService $healthScores,
        private readonly MonthlyClosingRepository $monthlyClosings,
    ) {}

    /**
     * "Disponible" en vivo para un período: mismo cálculo que el campo
     * totals.available de get(), pero sin armar el resto del DTO (sin período
     * anterior, by_category, by_user, overdue). Usado por SavingsWalletService
     * y SavingsGoalService para no permitir depositar/aportar más de lo que
     * queda sin asignar del mes (pedido explícito del usuario, 2026-09-21).
     */
    public function availableForPeriod(WorkspaceMembershipDto $membership, Period $period): float
    {
        $current = $this->dashboard->get($membership->workspaceId, $period);
        $this->servicePayments->markOverdue(
            $membership->workspaceId,
            CarbonImmutable::now(config('app.timezone'))->format('Y-m-d'),
        );
        $servicesPaidList = $this->servicePayments->list($membership->workspaceId, $period->year, $period->month);
        $servicesPaid = (float) $servicesPaidList['total_paid'];
        $installmentsPaidList = $this->installments->paymentsByPeriod(
            $membership->workspaceId,
            $period->year,
            $period->month,
        );
        $installmentsPaid = (float) $installmentsPaidList['total_paid'];
        $paidExpensesTotal = (float) $current['total_expenses'] + $servicesPaid + $installmentsPaid;

        $savingsTotals = $this->savingsWallet->totalsForPeriod($membership->workspaceId, $period->year, $period->month);
        $goalsTotals = $this->savingsGoals->totalsForPeriod($membership->workspaceId, $period->year, $period->month);
        $netSavings = ((float) $savingsTotals['deposited'] - (float) $savingsTotals['withdrawn'])
            + ((float) $goalsTotals['contributed'] - (float) $goalsTotals['withdrawn']);

        return (float) $current['total_income'] - $paidExpensesTotal - $netSavings;
    }

    public function get(WorkspaceMembershipDto $membership, Period $period): DashboardDto
    {
        $current = $this->dashboard->get($membership->workspaceId, $period);
        $previous = $this->dashboard->get($membership->workspaceId, $period->previous());

        // El job diario que marca vencidos (M-23) todavía no existe - se corre
        // acá también (idempotente, barato) para que el conteo sea siempre correcto.
        $this->servicePayments->markOverdue(
            $membership->workspaceId,
            CarbonImmutable::now(config('app.timezone'))->format('Y-m-d'),
        );
        $overdue = $this->servicePayments->listOverdue($membership->workspaceId);
        $currentServices = $this->servicePayments->list($membership->workspaceId, $period->year, $period->month);
        $previousPeriod = $period->previous();
        $previousServices = $this->servicePayments->list(
            $membership->workspaceId,
            $previousPeriod->year,
            $previousPeriod->month,
        );
        $currentServicesPaid = (float) $currentServices['total_paid'];
        $previousServicesPaid = (float) $previousServices['total_paid'];
        $totalExpenses = (float) $current['total_expenses'] + $currentServicesPaid;
        $previousExpenses = (float) $previous['total_expenses'] + $previousServicesPaid;
        $currentInstallments = $this->installments->paymentsByPeriod(
            $membership->workspaceId,
            $period->year,
            $period->month,
        );
        $previousInstallments = $this->installments->paymentsByPeriod(
            $membership->workspaceId,
            $previousPeriod->year,
            $previousPeriod->month,
        );
        $installmentsTotal = (float) $currentInstallments['total'];
        $previousInstallmentsTotal = (float) $previousInstallments['total'];
        $installmentsPaid = (float) $currentInstallments['total_paid'];
        $previousCommitted = $previousExpenses + $previousInstallmentsTotal;
        $committed = $totalExpenses + $installmentsTotal;
        $categoryExpenses = (float) $current['total_expenses'];
        $paidExpensesTotal = $categoryExpenses + $currentServicesPaid + $installmentsPaid;
        $savingsTotals = $this->savingsWallet->totalsForPeriod($membership->workspaceId, $period->year, $period->month);
        $savingsDeposited = (float) $savingsTotals['deposited'];
        $savingsWithdrawn = (float) $savingsTotals['withdrawn'];
        $goalsTotals = $this->savingsGoals->totalsForPeriod($membership->workspaceId, $period->year, $period->month);
        $goalsContributed = (float) $goalsTotals['contributed'];
        $goalsWithdrawn = (float) $goalsTotals['withdrawn'];
        $netSavings = ($savingsDeposited - $savingsWithdrawn) + ($goalsContributed - $goalsWithdrawn);
        $byCategory = $this->addServicesToByCategory($current['by_category'], $categoryExpenses, $currentServicesPaid);

        // Igual que el resto de este dashboard reducido (ver docblock de la
        // clase): siempre se calcula en vivo para el período pedido, sea
        // pasado o presente, en vez de leer la foto congelada de M-13.
        // sp_health_score_inputs ya filtra por year/month, así que un mes
        // cerrado da el mismo resultado que tendría el score persistido.
        $health = $this->healthScores->computeLive($membership->workspaceId, $period);

        $projection = $period->equals(Period::current())
            ? $this->computeProjection(
                $membership->workspaceId,
                $period,
                (float) $current['total_income'],
                $currentServicesPaid,
                $installmentsTotal,
                $previousCommitted,
                $netSavings,
            )
            : null;

        return new DashboardDto(
            $period->year,
            $period->month,
            $membership->workspaceCurrency,
            (float) $current['total_income'],
            $paidExpensesTotal,
            $categoryExpenses,
            $currentServicesPaid,
            $installmentsTotal,
            $installmentsPaid,
            $committed,
            (float) $current['total_income'] - $paidExpensesTotal - $netSavings,
            $savingsDeposited,
            $savingsWithdrawn,
            $goalsContributed,
            $goalsWithdrawn,
            $byCategory,
            array_values(array_map(
                fn (array $item) => [
                    'service_name' => (string) $item['service_name'],
                    'amount' => (float) ($item['amount_paid'] ?? 0),
                ],
                array_filter($currentServices['items'], fn (array $item) => $item['status'] === 'paid'),
            )),
            array_map(
                fn (array $item) => [
                    'description' => (string) $item['description'],
                    'number' => (int) $item['number'],
                    'installments_count' => (int) $item['installments_count'],
                    'amount' => (float) $item['amount'],
                    'status' => (string) $item['status'],
                ],
                $currentInstallments['items'],
            ),
            $this->addInstallmentsToUsers(
                $this->addServicesToUsers($current['by_user'], $currentServices['items']),
                $currentInstallments['items'],
            ),
            [
                'previous_income' => (float) $previous['total_income'],
                'previous_expenses' => $previousCommitted,
                'income_delta_pct' => $this->deltaPct(
                    (float) $previous['total_income'],
                    (float) $current['total_income'],
                ),
                'expenses_delta_pct' => $this->deltaPct($previousCommitted, $committed),
            ],
            array_map(
                fn (array $row) => [
                    'service_payment_id' => (int) $row['id'],
                    'service_name' => (string) $row['service_name'],
                    'amount' => (float) $row['expected_amount'],
                    'due_date_end' => (string) $row['due_date_end'],
                ],
                $overdue,
            ),
            $health,
            $this->pendingAllocation($membership->workspaceId),
            $projection,
        );
    }

    /** @return array{
     *     year:int,month:int,income:float,expenses:float,services:float,
     *     installments:float,available:float,is_closed:bool
     * }[]
     */
    public function history(int $workspaceId, int $months): array
    {
        $current = Period::current();
        $points = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $period = $current;
            for ($j = 0; $j < $i; $j++) {
                $period = $period->previous();
            }

            $closingRow = $this->monthlyClosings->get($workspaceId, $period);

            if ($closingRow !== null) {
                $totals = [
                    'income' => (float) $closingRow['total_income'],
                    'expenses' => (float) $closingRow['total_expenses'],
                    'services' => (float) $closingRow['total_services'],
                    'installments' => (float) $closingRow['total_installments'],
                ];
                $isClosed = true;
            } else {
                $computed = $this->monthlyClosings->compute($workspaceId, $period);
                $totals = [
                    'income' => (float) $computed['total_income'],
                    'expenses' => (float) $computed['total_expenses'],
                    'services' => (float) $computed['total_services'],
                    'installments' => (float) $computed['total_installments'],
                ];
                $isClosed = false;
            }

            $savingsTotals = $this->savingsWallet->totalsForPeriod($workspaceId, $period->year, $period->month);
            $goalsTotals = $this->savingsGoals->totalsForPeriod($workspaceId, $period->year, $period->month);
            $netSavings = ((float) $savingsTotals['deposited'] - (float) $savingsTotals['withdrawn'])
                + ((float) $goalsTotals['contributed'] - (float) $goalsTotals['withdrawn']);

            $points[] = [
                'year' => $period->year,
                'month' => $period->month,
                'income' => $totals['income'],
                'expenses' => $totals['expenses'],
                'services' => $totals['services'],
                'installments' => $totals['installments'],
                'available' => $totals['income'] - $totals['expenses'] - $totals['services']
                    - $totals['installments'] - $netSavings,
                'is_closed' => $isClosed,
            ];
        }

        return $points;
    }

    /**
     * Proyección de fin de mes (solo para el período actual, M-14 §4 paso 6).
     * Simplificada respecto al ideal de la spec: no descuenta servicios
     * pendientes de projected_available (este dashboard reducido no calcula
     * ese total en ningún otro lado tampoco) - servicios pagados e
     * installments ya comprometidos SÍ se descuentan, junto con el gasto
     * variable proyectado.
     *
     * @return array{
     *     days_elapsed:int,days_in_month:int,daily_average:float,
     *     projected_expenses:float,projected_available:float,trend:string
     * }|null
     */
    private function computeProjection(
        int $workspaceId,
        Period $period,
        float $income,
        float $servicesPaid,
        float $installmentsTotal,
        float $previousCommitted,
        float $netSavings,
    ): ?array {
        $now = CarbonImmutable::now(config('app.timezone'));
        $daysElapsed = $now->day;

        if ($daysElapsed < 1) {
            return null;
        }

        $daysInMonth = $period->daysInMonth();
        $dailySpend = $this->dashboard->dailySpend($workspaceId, $period, $now->format('Y-m-d'));
        $expensesToDate = (float) $dailySpend['expenses_to_date'];
        $dailyAverage = $expensesToDate / $daysElapsed;
        $projectedExpenses = $dailyAverage * $daysInMonth;
        $projectedAvailable = $income - $projectedExpenses - $servicesPaid - $installmentsTotal - $netSavings;

        $trend = 'on_track';
        if ($projectedAvailable < 0) {
            $trend = 'over';
        } elseif ($previousCommitted > 0 && $projectedExpenses < $previousCommitted * 0.9) {
            $trend = 'under';
        }

        return [
            'days_elapsed' => $daysElapsed,
            'days_in_month' => $daysInMonth,
            'daily_average' => round($dailyAverage, 2),
            'projected_expenses' => round($projectedExpenses, 2),
            'projected_available' => round($projectedAvailable, 2),
            'trend' => $trend,
        ];
    }

    /**
     * Último cierre con sobrante todavía sin asignar (M-13), si hay alguno -
     * alimenta el banner "Tenés $X sin asignar del cierre de mm/yyyy" del
     * dashboard. sp_monthly_closing_list ya viene ordenado year/month DESC.
     *
     * @return array{year:int,month:int,unallocated_amount:float}|null
     */
    private function pendingAllocation(int $workspaceId): ?array
    {
        foreach ($this->monthlyClosings->list($workspaceId) as $row) {
            $closing = MonthlyClosingDto::fromArray($row);

            if ($closing->allocationStatus === 'pending') {
                return [
                    'year' => $closing->year,
                    'month' => $closing->month,
                    'unallocated_amount' => (float) $closing->toArray()['unallocated_amount'],
                ];
            }
        }

        return null;
    }

    /**
     * Los servicios pagados no tienen categoría propia, pero deben verse en el
     * desglose "por categoría" del dashboard como un rubro más (rotula
     * "Servicios pagados") - se recalculan los % de todo el desglose contra la
     * base combinada (gastos por categoría + servicios pagados) para que sigan
     * sumando 100%.
     *
     * @param array<int, array{
     *     category_id:?int,category_name:string,category_color:string,category_icon:string,amount:float,pct:float
     * }> $byCategory
     * @return array<int, array{
     *     category_id:?int,category_name:string,category_color:string,category_icon:string,amount:float,pct:float
     * }>
     */
    private function addServicesToByCategory(array $byCategory, float $categoryExpenses, float $servicesPaid): array
    {
        if ($servicesPaid <= 0) {
            return $byCategory;
        }

        $combinedTotal = $categoryExpenses + $servicesPaid;
        $pct = fn (float $amount) => $combinedTotal > 0 ? round($amount / $combinedTotal * 100, 1) : 0.0;

        $byCategory = array_map(function (array $row) use ($pct) {
            $row['pct'] = $pct((float) $row['amount']);

            return $row;
        }, $byCategory);

        $byCategory[] = [
            'category_id' => null,
            'category_name' => 'Servicios pagados',
            'category_color' => '#9E9E9E',
            'category_icon' => 'receipt_long',
            'amount' => $servicesPaid,
            'pct' => $pct($servicesPaid),
        ];

        return $byCategory;
    }

    /**
     * Services are shared workspace expenses, but the payer identifies who
     * should absorb the amount in the member breakdown.
     *
     * @param array<int, array{user_id:int,user_name:string,income:float,expenses:float,balance:float}> $users
     * @param array<int, array{status:string,amount_paid:?float,paid_by_user_id:?int,paid_by_name:?string}> $payments
     * @return array<int, array{
     *     user_id:int,user_name:string,income:float,expenses:float,installments:float,balance:float
     * }>
     */
    private function addServicesToUsers(array $users, array $payments): array
    {
        $byId = [];
        foreach ($users as $index => $user) {
            $users[$index]['installments'] = 0.0;
            $byId[(int) $user['user_id']] = $index;
        }

        foreach ($payments as $payment) {
            if ($payment['status'] !== 'paid' || $payment['paid_by_user_id'] === null) {
                continue;
            }

            $userId = (int) $payment['paid_by_user_id'];
            $amount = (float) ($payment['amount_paid'] ?? 0);

            if (!isset($byId[$userId])) {
                $byId[$userId] = count($users);
                $users[] = [
                    'user_id' => $userId,
                    'user_name' => (string) ($payment['paid_by_name'] ?? 'Usuario'),
                    'income' => 0.0,
                    'expenses' => 0.0,
                    'installments' => 0.0,
                    'balance' => 0.0,
                ];
            }

            $index = $byId[$userId];
            $users[$index]['expenses'] += $amount;
            $users[$index]['balance'] = $users[$index]['income'] - $users[$index]['expenses'];
        }

        return $users;
    }

    /** @param array<int, array{
     *     user_id:int,user_name:string,income:float,expenses:float,installments:float,balance:float
     * }> $users
     */
    private function addInstallmentsToUsers(array $users, array $payments): array
    {
        $byId = [];
        foreach ($users as $index => $user) {
            $byId[(int) $user['user_id']] = $index;
        }

        foreach ($payments as $payment) {
            $userId = (int) $payment['user_id'];
            $amount = (float) $payment['amount'];
            if (!isset($byId[$userId])) {
                $byId[$userId] = count($users);
                $users[] = [
                    'user_id' => $userId,
                    'user_name' => 'Usuario',
                    'income' => 0.0,
                    'expenses' => 0.0,
                    'installments' => 0.0,
                    'balance' => 0.0,
                ];
            }
            $index = $byId[$userId];
            $users[$index]['installments'] += $amount;
            $users[$index]['balance'] = $users[$index]['income']
                - $users[$index]['expenses'] - $users[$index]['installments'];
        }

        return $users;
    }

    private function deltaPct(float $previous, float $current): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
