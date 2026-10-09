<?php

namespace App\Services\Settlement;

use App\DTOs\Settlement\SettlementBalanceDto;
use App\DTOs\Settlement\SettlementTransferDto;
use App\Support\Money;

/**
 * Clase pura, sin I/O (ver ESPECIFICACION_TECNICA.md M-25 §4.2). Recibe todo
 * ya resuelto por SettlementService y devuelve balances + liquidación
 * sugerida. Trabaja siempre en centavos enteros, nunca float, para que el
 * redondeo por resto mayor sea exacto y Σ balances == 0 siempre.
 *
 * balances[u] se calcula SOLO sobre gastos (paid - share): nunca se resta acá
 * lo ya liquidado, a propósito - es el histórico real de cuánto gastó cada
 * quien. pendingSettlements sí descuenta los settlementPayments ya
 * registrados (vía adjustedCents, interno, no se expone).
 */
class SettlementCalculator
{
    /**
     * @param array<int, array{user_id:int,name:string}> $members
     * @param array<int, array{amount:float|string,payer_user_id:int}> $expenses
     * @param array<int, array{from_user_id:int,to_user_id:int,amount:float|string}> $settlementPayments
     * @return array{balances: SettlementBalanceDto[], pendingSettlements: SettlementTransferDto[]}
     */
    public function calculate(array $members, array $expenses, array $settlementPayments): array
    {
        if (count($members) === 0) {
            return ['balances' => [], 'pendingSettlements' => []];
        }

        $names = [];
        foreach ($members as $member) {
            $names[(int) $member['user_id']] = (string) $member['name'];
        }

        $sortedUserIds = array_keys($names);
        sort($sortedUserIds);
        $memberCount = count($sortedUserIds);

        $totalCents = 0;
        foreach ($expenses as $expense) {
            $totalCents += Money::toCents((string) $expense['amount']);
        }

        $baseCents = intdiv($totalCents, $memberCount);
        $remainderCents = $totalCents % $memberCount;

        // Método del resto mayor: los primeros $remainderCents miembros (por
        // user_id ascendente) absorben 1 centavo extra, garantiza
        // Σ shareCents == totalCents exacto, sin diferencias de $0,01 sueltas.
        $shareCents = [];
        foreach ($sortedUserIds as $index => $userId) {
            $shareCents[$userId] = $baseCents + ($index < $remainderCents ? 1 : 0);
        }

        $paidCents = array_fill_keys($sortedUserIds, 0);
        foreach ($expenses as $expense) {
            $payerId = (int) $expense['payer_user_id'];
            $paidCents[$payerId] = ($paidCents[$payerId] ?? 0) + Money::toCents((string) $expense['amount']);
        }

        $balanceCents = [];
        foreach ($sortedUserIds as $userId) {
            $balanceCents[$userId] = ($paidCents[$userId] ?? 0) - $shareCents[$userId];
        }

        $adjustedCents = $balanceCents;
        foreach ($settlementPayments as $payment) {
            $amountCents = Money::toCents((string) $payment['amount']);
            $fromId = (int) $payment['from_user_id'];
            $toId = (int) $payment['to_user_id'];

            if (array_key_exists($fromId, $adjustedCents)) {
                $adjustedCents[$fromId] += $amountCents;
            }
            if (array_key_exists($toId, $adjustedCents)) {
                $adjustedCents[$toId] -= $amountCents;
            }
        }

        $balances = [];
        foreach ($sortedUserIds as $userId) {
            $balances[] = new SettlementBalanceDto(
                userId: $userId,
                userName: $names[$userId],
                paid: (float) Money::fromCents($paidCents[$userId] ?? 0),
                share: (float) Money::fromCents($shareCents[$userId]),
                balance: (float) Money::fromCents($balanceCents[$userId]),
            );
        }

        return [
            'balances' => $balances,
            'pendingSettlements' => $this->greedySettle($adjustedCents, $names),
        ];
    }

    /**
     * Greedy determinista (mayor deudor vs mayor acreedor, desempate por
     * user_id ascendente) - no garantiza el mínimo matemático global de
     * transferencias (NP-hard, P-37), pero acota a "miembros - 1" y coincide
     * con el óptimo en los casos típicos.
     *
     * @param array<int, int> $adjustedCents
     * @param array<int, string> $names
     * @return SettlementTransferDto[]
     */
    private function greedySettle(array $adjustedCents, array $names): array
    {
        $debtors = [];
        $creditors = [];
        foreach ($adjustedCents as $userId => $cents) {
            if ($cents < 0) {
                $debtors[] = ['user_id' => $userId, 'cents' => $cents];
            } elseif ($cents > 0) {
                $creditors[] = ['user_id' => $userId, 'cents' => $cents];
            }
        }

        usort(
            $debtors,
            fn (array $a, array $b) => ($a['cents'] <=> $b['cents']) ?: ($a['user_id'] <=> $b['user_id']),
        );
        usort(
            $creditors,
            fn (array $a, array $b) => ($b['cents'] <=> $a['cents']) ?: ($a['user_id'] <=> $b['user_id']),
        );

        $transfers = [];
        $debtorIndex = 0;
        $creditorIndex = 0;

        while ($debtorIndex < count($debtors) && $creditorIndex < count($creditors)) {
            $debtor = &$debtors[$debtorIndex];
            $creditor = &$creditors[$creditorIndex];
            $amountCents = min(-$debtor['cents'], $creditor['cents']);

            $transfers[] = new SettlementTransferDto(
                fromUserId: $debtor['user_id'],
                fromUserName: $names[$debtor['user_id']],
                toUserId: $creditor['user_id'],
                toUserName: $names[$creditor['user_id']],
                amount: (float) Money::fromCents($amountCents),
            );

            $debtor['cents'] += $amountCents;
            $creditor['cents'] -= $amountCents;

            if ($debtor['cents'] === 0) {
                $debtorIndex++;
            }
            if ($creditor['cents'] === 0) {
                $creditorIndex++;
            }
            unset($debtor, $creditor);
        }

        return $transfers;
    }
}
