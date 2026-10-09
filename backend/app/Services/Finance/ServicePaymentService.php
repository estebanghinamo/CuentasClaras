<?php

namespace App\Services\Finance;

use App\DTOs\Finance\ServicePaymentDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\InvalidStateException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use App\Repositories\ServicePaymentRepository;
use App\Services\Audit\AuditSummaries;
use App\Services\Closing\ClosingGuard;
use App\Services\Support\SpErrorMapper;
use App\Support\Money;
use App\Support\Period;
use Carbon\CarbonImmutable;

class ServicePaymentService
{
    public function __construct(
        private readonly ServicePaymentRepository $payments,
        private readonly ClosingGuard $closingGuard,
    ) {}

    public function listForPeriod(WorkspaceMembershipDto $membership, int $year, int $month): array
    {
        $period = Period::fromYearMonth($year, $month);
        $current = Period::current();

        // Antes solo se generaba la fila para el mes actual y el siguiente -
        // un servicio nuevo no aparecia si el usuario navegaba mas adelante
        // (ej. dentro de 3 meses) porque nadie habia "tocado" ese periodo
        // todavia. sp_service_payment_ensure_period es idempotente (INSERT
        // IGNORE) y no le importa cuando se creo el servicio, asi que es
        // seguro generarla para CUALQUIER periodo presente o futuro, no solo
        // current/current+1 - el pasado sigue sin tocarse a proposito.
        if (
            ($period->equals($current) || $period->isAfter($current))
            && $this->closingGuard->isOpen($membership->workspaceId, $period)
        ) {
            $this->payments->ensurePeriod($membership->workspaceId, $year, $month);
        }

        // El job diario que marca vencidos (M-23) todavía no existe: se corre acá
        // mismo, en el momento de leer, para que el estado que ve el usuario sea
        // siempre correcto sin depender de un cron. Barato (solo pendientes de
        // este workspace) e idempotente.
        $this->payments->markOverdue(
            $membership->workspaceId,
            CarbonImmutable::now(config('app.timezone'))->format('Y-m-d'),
        );

        $result = $this->payments->list($membership->workspaceId, $year, $month);

        return [
            'items' => array_map(fn (array $row) => ServicePaymentDto::fromArray($row), $result['items']),
            'total_expected' => (float) $result['total_expected'],
            'total_paid' => (float) $result['total_paid'],
            'pending_count' => (int) $result['pending_count'],
            'overdue_count' => (int) $result['overdue_count'],
        ];
    }

    /**
     * Pagar un service_payment. Deliberadamente NO llama a ClosingGuard: si el
     * período del pago ya está cerrado, se permite igual pagarlo tarde (P-13,
     * ver ESPECIFICACION_TECNICA.md M-08 §8) y el impacto en el balance se
     * computará por paid_at, no por year/month de la fila, cuando el dashboard
     * incorpore servicios (todavía no lo hace, ver M-14 "versión reducida").
     */
    public function pay(
        WorkspaceMembershipDto $membership,
        int $servicePaymentId,
        array $data,
        int $userId,
    ): ServicePaymentDto {
        $existing = $this->payments->find($membership->workspaceId, $servicePaymentId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        if ($existing['status'] === 'paid') {
            throw new InvalidStateException('Este servicio ya está pagado este mes.');
        }

        $paidAt = $data['paid_at'] ?? CarbonImmutable::now(config('app.timezone'))->format('Y-m-d');
        $wasLate = $paidAt > $existing['due_date_end'];
        $applyLateFee = $data['apply_late_fee'] ?? true;

        $base = isset($data['amount_paid']) ? (string) $data['amount_paid'] : (string) $existing['service_amount'];
        $calculatedFee = ($wasLate && $applyLateFee)
            ? ($existing['late_fee_type'] === 'percentage'
                ? Money::percent($base, (string) $existing['late_fee_value'])
                : (string) $existing['late_fee_value'])
            : '0';
        $feeDifference = isset($data['fee_difference']) ? (string) $data['fee_difference'] : '0';
        $lateFeeApplied = Money::add($calculatedFee, $feeDifference);
        $amountPaidFinal = Money::add($base, $lateFeeApplied);

        // No hay campo de notas libres en el dialog de pago (se reemplazó por
        // "diferencia" - ver ESPECIFICACION_TECNICA.md M-08): si el usuario
        // cargó una diferencia manual sobre el recargo calculado, queda un
        // rastro legible en el propio registro del pago.
        $notes = Money::cmp($feeDifference, '0') !== 0
            ? sprintf('Diferencia manual sobre el recargo: %s', $feeDifference)
            : ($data['notes'] ?? null);

        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('service_payment', 'paid', [
                'name' => $existing['service_name'],
                'period' => sprintf('%02d/%04d', $existing['month'], $existing['year']),
            ]),
        );

        try {
            $row = $this->payments->pay(
                $membership->workspaceId,
                $servicePaymentId,
                $amountPaidFinal,
                $lateFeeApplied,
                $paidAt,
                $wasLate,
                $userId,
                $notes,
                $audit,
            );
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return ServicePaymentDto::fromArray($row);
    }

    public function unpay(WorkspaceMembershipDto $membership, int $servicePaymentId, int $userId): ServicePaymentDto
    {
        $existing = $this->payments->find($membership->workspaceId, $servicePaymentId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        if ($existing['status'] !== 'paid') {
            throw new InvalidStateException('Este servicio no está pagado este mes.');
        }

        $period = Period::fromYearMonth((int) $existing['year'], (int) $existing['month']);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);

        $today = CarbonImmutable::now(config('app.timezone'))->format('Y-m-d');
        $newStatus = $today > $existing['due_date_end'] ? 'overdue' : 'pending';

        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('service_payment', 'unpaid', [
                'name' => $existing['service_name'],
                'period' => sprintf('%02d/%04d', $existing['month'], $existing['year']),
            ]),
        );

        try {
            $row = $this->payments->unpay($membership->workspaceId, $servicePaymentId, $newStatus, $audit);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return ServicePaymentDto::fromArray($row);
    }

    /** @return ServicePaymentDto[] */
    public function listOverdue(int $workspaceId): array
    {
        $this->payments->markOverdue($workspaceId, CarbonImmutable::now(config('app.timezone'))->format('Y-m-d'));

        return array_map(
            fn (array $row) => ServicePaymentDto::fromArray($row),
            $this->payments->listOverdue($workspaceId),
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
