<?php

namespace App\Repositories;

use App\Exceptions\StoredProcedureException;

class InstallmentRepository extends BaseRepository
{
    public function list(int $workspaceId, string $status): array
    {
        return $this->call('sp_installment_list', ['workspace_id' => $workspaceId, 'status' => $status]);
    }

    public function find(int $workspaceId, int $installmentId): ?array
    {
        try {
            return $this->call('sp_installment_get', [
                'workspace_id' => $workspaceId,
                'installment_id' => $installmentId,
            ]);
        } catch (StoredProcedureException $e) {
            if ($e->spCode === 'NOT_FOUND') {
                return null;
            }
            throw $e;
        }
    }

    public function create(
        int $workspaceId,
        int $userId,
        array $data,
        string $installmentAmount,
        array $payments,
        array $audit,
    ): array {
        return $this->call('sp_installment_create', [
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'description' => $data['description'],
            'total_amount' => (string) $data['total_amount'],
            'installments_count' => $data['installments_count'],
            'installment_amount' => $installmentAmount,
            'start_date' => $data['start_date'],
            'category_id' => $data['category_id'] ?? null,
            'payments' => $payments,
            'audit' => $audit,
        ]);
    }

    public function update(int $workspaceId, int $installmentId, array $data, array $audit): array
    {
        return $this->call('sp_installment_update', [
            'workspace_id' => $workspaceId,
            'installment_id' => $installmentId,
            'description' => $data['description'],
            'category_id' => $data['category_id'] ?? null,
            'audit' => $audit,
        ]);
    }

    public function delete(int $workspaceId, int $installmentId, string $mode, array $audit): array
    {
        return $this->call('sp_installment_delete', [
            'workspace_id' => $workspaceId,
            'installment_id' => $installmentId,
            'mode' => $mode,
            'audit' => $audit,
        ]);
    }

    public function pay(int $workspaceId, int $installmentId, int $paymentId, string $paidAt, array $audit): array
    {
        return $this->call('sp_installment_payment_pay', [
            'workspace_id' => $workspaceId,
            'installment_id' => $installmentId,
            'payment_id' => $paymentId,
            'paid_at' => $paidAt,
            'audit' => $audit,
        ]);
    }

    public function unpay(int $workspaceId, int $installmentId, int $paymentId, array $audit): array
    {
        return $this->call('sp_installment_payment_unpay', [
            'workspace_id' => $workspaceId,
            'installment_id' => $installmentId,
            'payment_id' => $paymentId,
            'audit' => $audit,
        ]);
    }

    public function paymentsByPeriod(int $workspaceId, int $year, int $month): array
    {
        return $this->call('sp_installment_payments_by_period', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function settlePeriod(int $workspaceId, int $year, int $month): int
    {
        $result = $this->call('sp_installment_payments_settle_period', [
            'workspace_id' => $workspaceId,
            'year' => $year,
            'month' => $month,
        ]);

        return (int) $result['updated_count'];
    }

    /** @return array{workspace_id:int,count:int,total:float}[] */
    public function dueSoonByWorkspace(int $year, int $month): array
    {
        return $this->call('sp_installment_payment_due_soon', ['year' => $year, 'month' => $month]);
    }
}
