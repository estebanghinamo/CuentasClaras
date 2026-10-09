<?php

namespace Tests\Unit\Settlement;

use App\DTOs\Settlement\SettlementBalanceDto;
use App\Services\Settlement\SettlementCalculator;
use App\Support\Money;

class SettlementCalculatorTest extends SettlementCalculatorTestCase
{
    private SettlementCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SettlementCalculator();
    }

    /** Punto 15 del pedido, caso 1: 2 personas, A paga $100, B paga $0 -> B le debe $50 a A. */
    public function test_two_members_one_pays_everything(): void
    {
        $members = [['user_id' => 1, 'name' => 'A'], ['user_id' => 2, 'name' => 'B']];
        $expenses = [['amount' => '100.00', 'payer_user_id' => 1]];

        $result = $this->calculator->calculate($members, $expenses, []);

        $this->assertBalances($result['balances'], [1 => 50.0, 2 => -50.0]);
        $this->assertCount(1, $result['pendingSettlements']);
        $this->assertTransfer($result['pendingSettlements'][0], from: 2, to: 1, amount: 50.0);
        $this->assertBalancesSumToZero($result['balances']);
    }

    /** Punto 15, caso 2: 3 personas, una paga todo ($300/0/0) -> los otros dos compensan hasta que todos soporten $100. */
    public function test_three_members_one_pays_all(): void
    {
        $members = [['user_id' => 1, 'name' => 'A'], ['user_id' => 2, 'name' => 'B'], ['user_id' => 3, 'name' => 'C']];
        $expenses = [['amount' => '300.00', 'payer_user_id' => 1]];

        $result = $this->calculator->calculate($members, $expenses, []);

        $this->assertBalances($result['balances'], [1 => 200.0, 2 => -100.0, 3 => -100.0]);
        $this->assertCount(2, $result['pendingSettlements']);
        foreach ($result['pendingSettlements'] as $transfer) {
            $this->assertSame(1, $transfer->toUserId);
            $this->assertSame(100.0, $transfer->amount);
        }
        $this->assertBalancesSumToZero($result['balances']);
    }

    /** Punto 15, caso 3: 4 personas con montos distintos (ejemplo del pedido original, Juan/María/Pedro/Ana). */
    public function test_four_members_with_different_amounts_settle_to_zero(): void
    {
        $members = [
            ['user_id' => 1, 'name' => 'Juan'],
            ['user_id' => 2, 'name' => 'María'],
            ['user_id' => 3, 'name' => 'Pedro'],
            ['user_id' => 4, 'name' => 'Ana'],
        ];
        $expenses = [
            ['amount' => '300000.00', 'payer_user_id' => 1],
            ['amount' => '120000.00', 'payer_user_id' => 2],
            ['amount' => '80000.00', 'payer_user_id' => 3],
            ['amount' => '300000.00', 'payer_user_id' => 4],
        ];

        $result = $this->calculator->calculate($members, $expenses, []);

        $this->assertBalances($result['balances'], [1 => 100000.0, 2 => -80000.0, 3 => -120000.0, 4 => 100000.0]);
        $this->assertLessThanOrEqual(3, count($result['pendingSettlements']));

        // Aplicar todas las transferencias sugeridas sobre los balances originales debe dejar a todos en 0.
        $adjusted = [1 => '100000.00', 2 => '-80000.00', 3 => '-120000.00', 4 => '100000.00'];
        foreach ($result['pendingSettlements'] as $transfer) {
            $adjusted[$transfer->fromUserId] = Money::add($adjusted[$transfer->fromUserId], (string) $transfer->amount);
            $adjusted[$transfer->toUserId] = Money::sub($adjusted[$transfer->toUserId], (string) $transfer->amount);
        }
        foreach ($adjusted as $balance) {
            $this->assertSame(0, Money::cmp($balance, '0'));
        }
    }

    /** Punto 15, caso 4: todos pagan exactamente lo mismo -> sin deudas. */
    public function test_all_members_pay_equally_has_no_pending_settlements(): void
    {
        $members = [['user_id' => 1, 'name' => 'A'], ['user_id' => 2, 'name' => 'B'], ['user_id' => 3, 'name' => 'C']];
        $expenses = [
            ['amount' => '50.00', 'payer_user_id' => 1],
            ['amount' => '50.00', 'payer_user_id' => 2],
            ['amount' => '50.00', 'payer_user_id' => 3],
        ];

        $result = $this->calculator->calculate($members, $expenses, []);

        $this->assertBalances($result['balances'], [1 => 0.0, 2 => 0.0, 3 => 0.0]);
        $this->assertCount(0, $result['pendingSettlements']);
    }

    /** Punto 15, caso 5: división no exacta ($100 entre 3) - resto mayor, sin diferencia de redondeo. */
    public function test_uneven_division_keeps_balances_summing_to_zero(): void
    {
        $members = [['user_id' => 1, 'name' => 'A'], ['user_id' => 2, 'name' => 'B'], ['user_id' => 3, 'name' => 'C']];
        $expenses = [['amount' => '100.00', 'payer_user_id' => 1]];

        $result = $this->calculator->calculate($members, $expenses, []);

        $shares = array_map(fn (SettlementBalanceDto $b) => $b->share, $result['balances']);
        sort($shares);
        $this->assertSame([33.33, 33.33, 33.34], $shares);
        $this->assertBalancesSumToZero($result['balances']);
    }

    /** Ya liquidado (settlement_payments) ajusta SOLO la liquidación sugerida, nunca el balance histórico expuesto. */
    public function test_registered_settlement_payment_does_not_change_historical_balance(): void
    {
        $members = [['user_id' => 1, 'name' => 'A'], ['user_id' => 2, 'name' => 'B']];
        $expenses = [['amount' => '100.00', 'payer_user_id' => 1]];
        $payments = [['from_user_id' => 2, 'to_user_id' => 1, 'amount' => '50.00']];

        $result = $this->calculator->calculate($members, $expenses, $payments);

        $this->assertBalances($result['balances'], [1 => 50.0, 2 => -50.0]);
        $this->assertCount(0, $result['pendingSettlements']);
    }

    /** §7: Σ balances(u) == 0 centavo a centavo para cualquier combinación aleatoria de gastos. */
    public function test_balances_always_sum_to_zero_for_random_data(): void
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $memberCount = random_int(2, 6);
            $members = [];
            for ($i = 1; $i <= $memberCount; $i++) {
                $members[] = ['user_id' => $i, 'name' => "User{$i}"];
            }

            $expenses = [];
            $expenseCount = random_int(1, 10);
            for ($i = 0; $i < $expenseCount; $i++) {
                $expenses[] = [
                    'amount' => number_format(random_int(1, 999999) / 100, 2, '.', ''),
                    'payer_user_id' => random_int(1, $memberCount),
                ];
            }

            $result = $this->calculator->calculate($members, $expenses, []);

            $this->assertBalancesSumToZero($result['balances'], "iteration {$iteration}");
        }
    }
}
