<?php

namespace App\Repositories;

class SettlementRepository extends BaseRepository
{
    public function getSnapshot(int $workspaceId): array
    {
        return $this->call('sp_settlement_snapshot_get', ['workspace_id' => $workspaceId]);
    }

    public function createPayment(int $workspaceId, array $data, array $audit): array
    {
        return $this->call('sp_settlement_payment_create', [
            'workspace_id' => $workspaceId,
            'from_user_id' => $data['from_user_id'],
            'to_user_id' => $data['to_user_id'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'registered_by' => $data['registered_by'],
            'audit' => $audit,
        ]);
    }

    public function deletePayment(int $workspaceId, int $settlementPaymentId, array $audit): void
    {
        $this->call('sp_settlement_payment_delete', [
            'workspace_id' => $workspaceId,
            'settlement_payment_id' => $settlementPaymentId,
            'audit' => $audit,
        ]);
    }

    public function findPaymentForAudit(int $workspaceId, int $settlementPaymentId): ?array
    {
        $snapshot = $this->getSnapshot($workspaceId);

        foreach ($snapshot['settlement_payments'] as $payment) {
            if ((int) $payment['id'] === $settlementPaymentId) {
                return $payment;
            }
        }

        return null;
    }
}
