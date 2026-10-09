<?php

namespace App\Services\Finance;

use App\DTOs\Finance\InstallmentDto;
use App\DTOs\Finance\InstallmentPaymentDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Repositories\InstallmentRepository;
use App\Services\Audit\AuditSummaries;
use App\Services\Closing\ClosingGuard;
use App\Services\Workspace\WorkspacePermissions;
use App\Support\Money;
use App\Support\Period;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class InstallmentService
{
    public function __construct(
        private readonly InstallmentRepository $installments,
        private readonly ClosingGuard $closingGuard,
        private readonly WorkspacePermissions $permissions,
    ) {}

    /** @return InstallmentDto[] */
    public function list(WorkspaceMembershipDto $membership, string $status = 'active'): array
    {
        return array_map(
            fn (array $row) => InstallmentDto::fromArray($row),
            $this->installments->list($membership->workspaceId, $status),
        );
    }

    public function get(WorkspaceMembershipDto $membership, int $installmentId): InstallmentDto
    {
        $row = $this->installments->find($membership->workspaceId, $installmentId);

        if ($row === null) {
            throw new NotFoundException();
        }

        return InstallmentDto::fromArray($row);
    }

    public function create(WorkspaceMembershipDto $membership, array $data): InstallmentDto
    {
        $startPeriod = Period::fromDate($data['start_date']);
        $this->assertStartDateAllowed($data['start_date']);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $startPeriod);

        $count = (int) $data['installments_count'];
        $total = (string) $data['total_amount'];
        $baseAmount = Money::div($total, (string) $count);
        $payments = [];
        $start = CarbonImmutable::parse($data['start_date'], config('app.timezone'))->startOfMonth();

        for ($number = 1; $number <= $count; $number++) {
            $amount = $number === $count
                ? Money::sub($total, Money::mul($baseAmount, (string) ($count - 1)))
                : $baseAmount;
            $period = Period::fromYearMonth((int) $start->year, (int) $start->month);
            $payments[] = [
                'number' => $number,
                'year' => $period->year,
                'month' => $period->month,
                'amount' => $amount,
            ];
            $start = $start->addMonthNoOverflow();
        }

        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('installment', 'created', ['description' => $data['description']]),
        );
        $row = $this->installments->create(
            $membership->workspaceId,
            $membership->userId,
            $data,
            $baseAmount,
            $payments,
            $audit,
        );

        return InstallmentDto::fromArray($row);
    }

    public function update(WorkspaceMembershipDto $membership, int $installmentId, array $data): InstallmentDto
    {
        $existing = $this->get($membership, $installmentId);
        $this->permissions->assertCanEditPersonalRecord($membership, $existing->userId);

        if ($existing->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => ['No se puede editar una compra cancelada.']]);
        }

        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('installment', 'updated', ['description' => $data['description']]),
        );
        $row = $this->installments->update($membership->workspaceId, $installmentId, $data, $audit);

        return InstallmentDto::fromArray($row);
    }

    public function delete(WorkspaceMembershipDto $membership, int $installmentId): void
    {
        $existing = $this->get($membership, $installmentId);
        $this->permissions->assertCanEditPersonalRecord($membership, $existing->userId);

        $mode = $this->deleteMode($membership, $existing);
        $audit = $this->auditPayload(
            $membership->userId,
            AuditSummaries::for('installment', 'deleted', ['description' => $existing->description]),
        );
        $this->installments->delete($membership->workspaceId, $installmentId, $mode, $audit);
    }

    public function pay(
        WorkspaceMembershipDto $membership,
        int $installmentId,
        int $paymentId,
        ?string $paidAt,
        int $userId,
    ): InstallmentPaymentDto {
        $existing = $this->get($membership, $installmentId);
        $this->permissions->assertCanEditPersonalRecord($membership, $existing->userId);
        $payment = $this->findPayment($existing, $paymentId);
        $this->assertNoUnpaidPredecessors($existing, $payment);
        $period = Period::fromYearMonth($payment->year, $payment->month);
        $this->closingGuard->assertPeriodOpen($membership->workspaceId, $period);
        $date = $paidAt ?? CarbonImmutable::now(config('app.timezone'))->format('Y-m-d');
        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('installment_payment', 'paid', [
                'description' => $existing->description,
                'number' => $payment->number,
            ]),
        );
        $row = $this->installments->pay($membership->workspaceId, $installmentId, $paymentId, $date, $audit);

        return InstallmentPaymentDto::fromArray($row);
    }

    public function unpay(
        WorkspaceMembershipDto $membership,
        int $installmentId,
        int $paymentId,
        int $userId,
    ): InstallmentPaymentDto {
        $existing = $this->get($membership, $installmentId);
        $this->permissions->assertCanEditPersonalRecord($membership, $existing->userId);
        $payment = $this->findPayment($existing, $paymentId);
        $this->assertNoPaidSuccessors($existing, $payment);
        $this->closingGuard->assertPeriodOpen(
            $membership->workspaceId,
            Period::fromYearMonth($payment->year, $payment->month),
        );
        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('installment_payment', 'unpaid', [
                'description' => $existing->description,
                'number' => $payment->number,
            ]),
        );
        $row = $this->installments->unpay($membership->workspaceId, $installmentId, $paymentId, $audit);

        return InstallmentPaymentDto::fromArray($row);
    }

    public function paymentsByPeriod(WorkspaceMembershipDto $membership, int $year, int $month): array
    {
        return $this->installments->paymentsByPeriod($membership->workspaceId, $year, $month);
    }

    private function deleteMode(WorkspaceMembershipDto $membership, InstallmentDto $installment): string
    {
        foreach ($installment->payments ?? [] as $payment) {
            $periodOpen = $this->closingGuard->isOpen(
                $membership->workspaceId,
                Period::fromYearMonth($payment->year, $payment->month),
            );
            if ($payment->status === 'paid' || !$periodOpen) {
                return 'cancel';
            }
        }

        return 'hard';
    }

    private function findPayment(InstallmentDto $installment, int $paymentId): InstallmentPaymentDto
    {
        foreach ($installment->payments ?? [] as $payment) {
            if ($payment->id === $paymentId) {
                return $payment;
            }
        }

        throw new NotFoundException();
    }

    /** Las cuotas se pagan en orden: no se puede pagar la N si alguna anterior sigue pendiente. */
    private function assertNoUnpaidPredecessors(InstallmentDto $installment, InstallmentPaymentDto $payment): void
    {
        foreach ($installment->payments ?? [] as $other) {
            if ($other->number < $payment->number && $other->status !== 'paid') {
                throw ValidationException::withMessages([
                    'payment' => ["Primero tenés que pagar la cuota {$other->number}."],
                ]);
            }
        }
    }

    /** Simétrico a assertNoUnpaidPredecessors: no se puede deshacer la N si alguna posterior ya esta pagada. */
    private function assertNoPaidSuccessors(InstallmentDto $installment, InstallmentPaymentDto $payment): void
    {
        foreach ($installment->payments ?? [] as $other) {
            if ($other->number > $payment->number && $other->status === 'paid') {
                throw ValidationException::withMessages([
                    'payment' => ["Primero tenés que deshacer el pago de la cuota {$other->number}."],
                ]);
            }
        }
    }

    private function assertStartDateAllowed(string $date): void
    {
        $start = CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
        $limit = CarbonImmutable::now(config('app.timezone'))->addMonthsNoOverflow(12)->endOfDay();

        if ($start->isAfter($limit)) {
            throw ValidationException::withMessages([
                'start_date' => ['La compra no puede comenzar más de 12 meses adelante.'],
            ]);
        }
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
