<?php

namespace App\Services\Settlement;

use App\DTOs\Settlement\SettlementPaymentDto;
use App\DTOs\Settlement\SettlementSummaryDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use App\Repositories\SettlementRepository;
use App\Repositories\WorkspaceRepository;
use App\Services\Audit\AuditSummaries;
use App\Services\Support\SpErrorMapper;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    public function __construct(
        private readonly SettlementRepository $settlements,
        private readonly WorkspaceRepository $workspaces,
        private readonly SettlementCalculator $calculator,
    ) {}

    public function getSummary(WorkspaceMembershipDto $membership): SettlementSummaryDto
    {
        $snapshot = $this->settlements->getSnapshot($membership->workspaceId);

        $result = $this->calculator->calculate(
            $snapshot['members'],
            $snapshot['expenses'],
            $snapshot['settlement_payments'],
        );

        $totalExpenses = array_reduce(
            $snapshot['expenses'],
            fn (string $carry, array $expense) => Money::add($carry, (string) $expense['amount']),
            '0',
        );
        $memberCount = count($snapshot['members']);

        return new SettlementSummaryDto(
            totalExpenses: (float) $totalExpenses,
            memberCount: $memberCount,
            sharePerPerson: $memberCount > 0 ? (float) Money::div($totalExpenses, (string) $memberCount) : 0.0,
            balances: $result['balances'],
            pendingSettlements: $result['pendingSettlements'],
            settledPayments: array_map(
                fn (array $row) => SettlementPaymentDto::fromArray($row),
                $snapshot['settlement_payments'],
            ),
        );
    }

    public function registerPayment(WorkspaceMembershipDto $membership, int $userId, array $data): SettlementPaymentDto
    {
        $fromUserId = (int) $data['from_user_id'];
        $toUserId = (int) $data['to_user_id'];

        if ($fromUserId === $toUserId) {
            throw ValidationException::withMessages([
                'to_user_id' => ['No podés registrar un pago de una persona a sí misma.'],
            ]);
        }

        $members = $this->workspaces->listMembers($membership->workspaceId);
        $names = [];
        foreach ($members as $member) {
            $names[(int) $member['user_id']] = (string) $member['name'];
        }

        if (!isset($names[$fromUserId]) || !isset($names[$toUserId])) {
            throw new NotFoundException();
        }

        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('settlement_payment', 'created', [
                'amount' => (float) $data['amount'],
                'from_name' => $names[$fromUserId],
                'to_name' => $names[$toUserId],
            ]),
        );

        try {
            $row = $this->settlements->createPayment($membership->workspaceId, [
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'registered_by' => $userId,
            ], $audit);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return SettlementPaymentDto::fromArray($row);
    }

    public function deletePayment(WorkspaceMembershipDto $membership, int $userId, int $settlementPaymentId): void
    {
        $existing = $this->settlements->findPaymentForAudit($membership->workspaceId, $settlementPaymentId);

        if ($existing === null) {
            throw new NotFoundException();
        }

        $audit = $this->auditPayload(
            $userId,
            AuditSummaries::for('settlement_payment', 'deleted', [
                'amount' => (float) $existing['amount'],
                'from_name' => $existing['from_user_name'],
                'to_name' => $existing['to_user_name'],
            ]),
        );

        try {
            $this->settlements->deletePayment($membership->workspaceId, $settlementPaymentId, $audit);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
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
