<?php

namespace Tests\Feature\Savings;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class SavingsWalletTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'savings_goal_movements', 'savings_goals', 'savings_movements', 'savings_wallet',
            'installment_payments', 'installments', 'monthly_closings', 'expenses', 'income_entries', 'categories',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function addIncome(array $ctx, float $amount): void
    {
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => $amount,
            'concept' => 'Sueldo',
            'date' => now()->startOfMonth()->addDays(9)->format('Y-m-d'),
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
    }

    public function test_wallet_starts_at_zero_for_a_new_workspace(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/wallet", [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->assertStatus(200)
            ->assertJsonPath('data.balance', 0)
            ->assertJsonPath('data.total_deposited', 0)
            ->assertJsonPath('data.total_withdrawn', 0);
    }

    public function test_deposit_and_withdraw_update_balance_and_ledger(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 1000);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
            'type' => 'deposit',
            'amount' => 1000,
        ], $auth)->assertStatus(201)
            ->assertJsonPath('data.balance_after', 1000)
            ->assertJsonPath('data.type', 'deposit');

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
            'type' => 'withdraw',
            'amount' => 300,
            'note' => 'compra',
        ], $auth)->assertStatus(201)
            ->assertJsonPath('data.balance_after', 700)
            ->assertJsonPath('data.note', 'compra');

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/wallet", $auth)
            ->assertStatus(200)
            ->assertJsonPath('data.balance', 700)
            ->assertJsonPath('data.total_deposited', 1000)
            ->assertJsonPath('data.total_withdrawn', 300);
    }

    public function test_withdraw_more_than_balance_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 100);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
            'type' => 'deposit',
            'amount' => 100,
        ], $auth)->assertStatus(201);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
            'type' => 'withdraw',
            'amount' => 500,
        ], $auth)->assertStatus(422)->assertJsonPath('error.code', 'INSUFFICIENT_FUNDS');

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/wallet", $auth)
            ->assertStatus(200)
            ->assertJsonPath('data.balance', 100);
    }

    public function test_deposit_more_than_available_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 200);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
            'type' => 'deposit',
            'amount' => 500,
        ], $auth)->assertStatus(422)->assertJsonPath('error.code', 'INSUFFICIENT_FUNDS');

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/wallet", $auth)
            ->assertStatus(200)
            ->assertJsonPath('data.balance', 0);
    }

    public function test_movements_list_is_paginated_and_filterable_by_type(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 700);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", ['type' => 'deposit', 'amount' => 500], $auth)->assertStatus(201);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", ['type' => 'withdraw', 'amount' => 100], $auth)->assertStatus(201);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", ['type' => 'deposit', 'amount' => 200], $auth)->assertStatus(201);

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", $auth)
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements?type=withdraw", $auth)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_wallet_history_returns_requested_number_of_months_with_carry_over(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 1000);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", ['type' => 'deposit', 'amount' => 1000], $auth)->assertStatus(201);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/wallet/history?months=6", $auth);

        $response->assertStatus(200)->assertJsonCount(6, 'data');
        $this->assertSame(1000.0, (float) $response->json('data.5.balance_end'));
        $this->assertSame(0.0, (float) $response->json('data.0.balance_end'));
    }
}
