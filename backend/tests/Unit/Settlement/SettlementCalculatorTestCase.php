<?php

namespace Tests\Unit\Settlement;

use App\DTOs\Settlement\SettlementBalanceDto;
use App\DTOs\Settlement\SettlementTransferDto;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

abstract class SettlementCalculatorTestCase extends TestCase
{
    /** @param SettlementBalanceDto[] $balances @param array<int, float> $expected user_id => balance */
    protected function assertBalances(array $balances, array $expected): void
    {
        $byId = [];
        foreach ($balances as $balance) {
            $byId[$balance->userId] = $balance->balance;
        }

        foreach ($expected as $userId => $balance) {
            $this->assertSame($balance, $byId[$userId], "balance for user {$userId}");
        }
    }

    protected function assertTransfer(SettlementTransferDto $transfer, int $from, int $to, float $amount): void
    {
        $this->assertSame($from, $transfer->fromUserId);
        $this->assertSame($to, $transfer->toUserId);
        $this->assertSame($amount, $transfer->amount);
    }

    /** @param SettlementBalanceDto[] $balances */
    protected function assertBalancesSumToZero(array $balances, string $message = ''): void
    {
        $sum = '0';
        foreach ($balances as $balance) {
            $sum = Money::add($sum, (string) $balance->balance);
        }

        $this->assertSame(0, Money::cmp($sum, '0'), $message);
    }
}
