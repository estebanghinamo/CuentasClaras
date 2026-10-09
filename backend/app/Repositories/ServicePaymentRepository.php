<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class ServicePaymentRepository extends BaseRepository
{
    public function ensurePeriod(int $workspaceId, int $year, int $month): int
    {
        $result = $this->call('sp_service_payment_ensure_period', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);

        return (int) $result['created_count'];
    }

    public function list(int $workspaceId, int $year, int $month): array
    {
        return $this->call('sp_service_payment_list', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function find(int $workspaceId, int $servicePaymentId): ?array
    {
        try {
            return $this->call('sp_service_payment_get', [
                'workspace_id' => $workspaceId,
                'service_payment_id' => $servicePaymentId,
            ]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function pay(
        int $workspaceId,
        int $servicePaymentId,
        string $amountPaid,
        string $lateFeeApplied,
        string $paidAt,
        bool $wasLate,
        int $paidByUserId,
        ?string $notes,
        array $audit,
    ): array {
        return $this->call('sp_service_payment_pay', [
            'workspace_id' => $workspaceId,
            'service_payment_id' => $servicePaymentId,
            'amount_paid' => $amountPaid,
            'late_fee_applied' => $lateFeeApplied,
            'paid_at' => $paidAt,
            'was_late' => $wasLate,
            'paid_by_user_id' => $paidByUserId,
            'notes' => $notes,
            'audit' => $audit,
        ]);
    }

    public function unpay(int $workspaceId, int $servicePaymentId, string $newStatus, array $audit): array
    {
        return $this->call('sp_service_payment_unpay', [
            'workspace_id' => $workspaceId,
            'service_payment_id' => $servicePaymentId,
            'new_status' => $newStatus,
            'audit' => $audit,
        ]);
    }

    public function markOverdue(?int $workspaceId, string $today): int
    {
        $result = $this->call('sp_service_payment_mark_overdue', [
            'workspace_id' => $workspaceId,
            'today' => $today,
        ]);

        return (int) $result['updated_count'];
    }

    public function listOverdue(int $workspaceId, bool $updatedTodayOnly = false): array
    {
        return $this->call('sp_service_payment_list_overdue', [
            'workspace_id' => $workspaceId,
            'updated_today_only' => $updatedTodayOnly,
        ]);
    }

    /** @return array{
     *     service_payment_id:int,workspace_id:int,service_id:int,service_name:string,amount:float,due_date:string
     * }[]
     */
    public function dueSoon(string $date): array
    {
        return $this->call('sp_service_payment_due_soon', ['date' => $date]);
    }
}
