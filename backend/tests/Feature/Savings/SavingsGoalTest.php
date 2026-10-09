<?php

namespace Tests\Feature\Savings;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class SavingsGoalTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'savings_goal_movements', 'savings_goals', 'savings_movements', 'savings_wallet',
            'installment_payments', 'installments', 'monthly_closings', 'expenses', 'income_entries', 'categories',
            'notifications', 'notification_preferences',
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

    private function createGoal(array $ctx, array $overrides = []): array
    {
        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals", array_merge([
            'name' => 'Viaje',
            'target_amount' => 1000,
        ], $overrides), ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(201);

        return $response->json('data');
    }

    public function test_contributing_up_to_target_completes_the_goal(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 1100);
        $goal = $this->createGoal($ctx);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'contribution',
            'amount' => 600,
        ], $auth)->assertStatus(200)->assertJsonPath('data.status', 'active')->assertJsonPath('data.progress_pct', 60);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'contribution',
            'amount' => 500,
        ], $auth)->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.current_amount', 1100)
            ->assertJsonPath('data.remaining_amount', 0);

        $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 1);
        $notification = $this->getJson('/api/notifications', $auth)->json('data.0');
        $this->assertSame('savings_goal_completed', $notification['type']);
    }

    public function test_movements_lists_contributions_and_withdrawals_in_order(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 1000);
        $goal = $this->createGoal($ctx);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'contribution',
            'amount' => 300,
            'note' => 'Aporte inicial',
        ], $auth)->assertStatus(200);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'withdrawal',
            'amount' => 100,
        ], $auth)->assertStatus(200);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/movements",
            $auth,
        );

        $response->assertStatus(200);
        $movements = $response->json('data');
        $this->assertCount(2, $movements);
        $this->assertSame(['contribution', 'withdrawal'], array_column($movements, 'type'));
        $this->assertSame('Aporte inicial', $movements[0]['note']);
        $this->assertEquals(100, $movements[1]['amount']);
    }

    public function test_cannot_contribute_to_a_completed_goal_but_can_withdraw_to_reopen_it(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 600);
        $goal = $this->createGoal($ctx, ['target_amount' => 500]);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'contribution',
            'amount' => 500,
        ], $auth)->assertStatus(200)->assertJsonPath('data.status', 'completed');

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'contribution',
            'amount' => 10,
        ], $auth)->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'withdrawal',
            'amount' => 100,
        ], $auth)->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.current_amount', 400);
    }

    public function test_withdrawal_over_current_amount_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $goal = $this->createGoal($ctx);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'withdrawal',
            'amount' => 50,
        ], $auth)->assertStatus(422)->assertJsonPath('error.code', 'INSUFFICIENT_FUNDS');
    }

    public function test_contribution_more_than_available_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 200);
        $goal = $this->createGoal($ctx);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'contribution',
            'amount' => 500,
        ], $auth)->assertStatus(422)->assertJsonPath('error.code', 'INSUFFICIENT_FUNDS');
    }

    public function test_cannot_lower_target_below_current_amount(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 900);
        $goal = $this->createGoal($ctx);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/contribute", [
            'type' => 'contribution',
            'amount' => 900,
        ], $auth)->assertStatus(200);

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}", [
            'name' => 'Viaje',
            'target_amount' => 500,
        ], $auth)->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_cancel_goal(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $goal = $this->createGoal($ctx);

        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}", [], $auth)->assertStatus(204);

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals?status=cancelled", $auth)
            ->assertStatus(200)
            ->assertJsonPath('data.0.status', 'cancelled');
    }

    public function test_transfer_from_wallet_moves_money_atomically(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $this->addIncome($ctx, 2000);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
            'type' => 'deposit',
            'amount' => 2000,
        ], $auth)->assertStatus(201);

        $goal = $this->createGoal($ctx, ['target_amount' => 5000]);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/transfer-from-wallet", [
            'amount' => 1500,
        ], $auth)->assertStatus(200)
            ->assertJsonPath('data.wallet_balance', 500)
            ->assertJsonPath('data.goal.current_amount', 1500);

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/savings/wallet", $auth)
            ->assertStatus(200)
            ->assertJsonPath('data.balance', 500);
    }

    public function test_transfer_more_than_wallet_balance_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $goal = $this->createGoal($ctx, ['target_amount' => 5000]);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals/{$goal['id']}/transfer-from-wallet", [
            'amount' => 100,
        ], $auth)->assertStatus(422)->assertJsonPath('error.code', 'INSUFFICIENT_FUNDS');
    }
}
