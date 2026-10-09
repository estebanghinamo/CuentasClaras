<?php

namespace App\Services\Notifications;

use App\Repositories\InstallmentRepository;
use App\Repositories\ServicePaymentRepository;
use App\Repositories\WorkspaceRepository;
use App\Support\Period;
use Carbon\CarbonImmutable;

/**
 * Jobs diarios de recordatorios (ESPECIFICACION_TECNICA.md M-17 §4). Corridos
 * desde SendServiceRemindersJob/SendInstallmentRemindersJob/SendOverdueDigestJob
 * (M-23, scheduler diario). La lógica vive acá (no en los Jobs) para poder
 * testear sin encolar de verdad, mismo patrón que ScanSmartSuggestionsJob.
 */
class ReminderService
{
    /**
     * Días de anticipación cubiertos por el job (unión de los defaults de la
     * spec [3,1] + "vence hoy" [0]) - simplificación deliberada respecto al
     * "0..30" que sugiere la spec como alternativa: correr sp_service_payment_due_soon
     * 31 veces por día sería carísimo para casi ningún beneficio real, ya que
     * casi nadie va a configurar un día de recordatorio fuera de este rango.
     * Si un usuario configura un día fuera de {0,1,3} en sus preferencias,
     * simplemente no va a recibir ese recordatorio puntual - documentado como
     * limitación conocida.
     */
    private const REMINDER_DAYS = [0, 1, 3];

    public function __construct(
        private readonly ServicePaymentRepository $servicePayments,
        private readonly InstallmentRepository $installments,
        private readonly WorkspaceRepository $workspaces,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function sendServiceReminders(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'));

        foreach (self::REMINDER_DAYS as $daysAhead) {
            $targetDate = $today->addDays($daysAhead)->format('Y-m-d');

            foreach ($this->servicePayments->dueSoon($targetDate) as $row) {
                $this->notifyServiceDueSoon($row, $daysAhead);
            }
        }
    }

    private function notifyServiceDueSoon(array $row, int $daysAhead): void
    {
        $workspaceId = (int) $row['workspace_id'];
        $memberIds = $this->memberIdsOf($workspaceId);

        $title = $daysAhead === 0
            ? "{$row['service_name']} vence hoy"
            : "{$row['service_name']} vence en {$daysAhead} día".($daysAhead > 1 ? 's' : '');

        $this->dispatcher->notifyUsers(
            $memberIds,
            'service_due_soon',
            $title,
            "Vencimiento: {$row['due_date']} - $".number_format((float) $row['amount'], 2),
            '/w/'.$workspaceId.'/services',
            $workspaceId,
            [
                'service_id' => $row['service_id'], 'service_payment_id' => $row['service_payment_id'],
                'service_name' => $row['service_name'], 'amount' => $row['amount'],
                'due_date' => $row['due_date'], 'days' => $daysAhead,
            ],
        );
    }

    /** Avisa una vez al inicio del mes con el total de cuotas pendientes
     * (P-24: las cuotas no tienen día de vencimiento propio). */
    public function sendInstallmentReminders(): void
    {
        $period = Period::current();

        foreach ($this->installments->dueSoonByWorkspace($period->year, $period->month) as $row) {
            $workspaceId = (int) $row['workspace_id'];
            $count = (int) $row['count'];
            $total = (float) $row['total'];

            $this->dispatcher->notifyUsers(
                $this->memberIdsOf($workspaceId),
                'installment_due_soon',
                'Cuotas del mes',
                "Este mes tenés {$count} cuota".($count > 1 ? 's' : '')." por \${$this->formatMoney($total)}.",
                '/w/'.$workspaceId.'/installments',
                $workspaceId,
                ['count' => $count, 'total' => $total, 'year' => $period->year, 'month' => $period->month],
            );
        }
    }

    /**
     * Corre sp_service_payment_mark_overdue primero (M-08) - no existe todavía
     * un job propio para eso (EnsureServicePaymentsJob/MarkOverdueServicePaymentsJob
     * quedan pendientes de M-23, ver PROJECT_STATE.json), así que este job se
     * encarga de dispararlo él mismo antes de armar el digest del día.
     */
    public function sendOverdueDigest(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'))->format('Y-m-d');
        $this->servicePayments->markOverdue(null, $today);

        foreach ($this->workspaces->listAllIds() as $workspaceId) {
            // El filtro "vencio hoy" lo hace la propia SP comparando contra su
            // CURDATE() (updated_today_only=true) - no contra $today calculado
            // acá en PHP, porque updated_at lo graba MySQL con su propio reloj
            // (ON UPDATE CURRENT_TIMESTAMP) y puede estar en otro timezone que
            // APP_TIMEZONE, desalineandose cerca de la medianoche local.
            $overdueToday = $this->servicePayments->listOverdue($workspaceId, updatedTodayOnly: true);

            if ($overdueToday === []) {
                continue;
            }

            $memberIds = $this->memberIdsOf($workspaceId);

            foreach ($overdueToday as $row) {
                $this->dispatcher->notifyUsers(
                    $memberIds,
                    'service_overdue',
                    "{$row['service_name']} venció",
                    "Venció el {$row['due_date_end']} - \${$this->formatMoney((float) $row['expected_amount'])}.",
                    '/w/'.$workspaceId.'/services',
                    $workspaceId,
                    [
                        'service_payment_id' => $row['id'],
                        'service_id' => $row['service_id'],
                        'service_name' => $row['service_name'],
                    ],
                );
            }
        }
    }

    /** @return int[] */
    private function memberIdsOf(int $workspaceId): array
    {
        return array_map(fn (array $m) => (int) $m['user_id'], $this->workspaces->listMembers($workspaceId));
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2);
    }
}
