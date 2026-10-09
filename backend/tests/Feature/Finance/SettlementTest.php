<?php

namespace Tests\Feature\Finance;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'settlement_payments', 'expenses', 'monthly_closings', 'categories', 'savings_wallet',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function currentPeriodDate(int $day = 10): string
    {
        return now()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
    }

    /** @return array{token:string,user_id:int} */
    private function addMember(array $ctx): array
    {
        $member = $this->registerUser();

        $code = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->json('data.code');

        $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$member['token']}"])
            ->assertStatus(200);

        return $member;
    }

    private function createExpense(array $ctx, float $amount, ?int $paidByUserId = null): int
    {
        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => $amount,
            'payment_method' => 'cash',
            'date' => $this->currentPeriodDate(),
            'paid_by_user_id' => $paidByUserId,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

        return (int) $response->json('data.id');
    }

    public function test_create_shared_settlement_workspace(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_settlement']);

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}", ['Authorization' => "Bearer {$ctx['token']}"])
            ->assertStatus(200)
            ->assertJsonPath('data.type', 'shared_settlement');

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/categories", ['Authorization' => "Bearer {$ctx['token']}"])
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_expense_paid_by_another_member_shows_up_under_the_payer(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_settlement']);
        $member = $this->addMember($ctx);

        $this->createExpense($ctx, 100.00, $member['user_id']);

        $summary = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/settlement", ['Authorization' => "Bearer {$ctx['token']}"])
            ->assertStatus(200)
            ->json('data');

        $memberBalance = collect($summary['balances'])->firstWhere('user_id', $member['user_id']);
        $ownerBalance = collect($summary['balances'])->firstWhere('user_id', $ctx['user_id']);

        $this->assertSame(100, $memberBalance['paid']);
        $this->assertSame(0, $ownerBalance['paid']);
    }

    public function test_paid_by_user_id_outside_workspace_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_settlement']);
        $stranger = $this->registerUser();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 100.00,
            'payment_method' => 'cash',
            'date' => $this->currentPeriodDate(),
            'paid_by_user_id' => $stranger['user_id'],
        ], ['Authorization' => "Bearer {$ctx['token']}"])
            ->assertStatus(422)
            ->assertJsonPath('error.details.paid_by_user_id.0', 'La persona seleccionada no pertenece a este workspace.');
    }

    public function test_member_can_edit_expense_paid_by_another_member(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_settlement']);
        $member = $this->addMember($ctx);

        $expenseId = $this->createExpense($ctx, 50.00);

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/expenses/{$expenseId}", [
            'amount' => 75.00,
            'payment_method' => 'cash',
            'date' => $this->currentPeriodDate(),
        ], ['Authorization' => "Bearer {$member['token']}"])
            ->assertStatus(200)
            ->assertJsonPath('data.amount', 75);
    }

    public function test_mark_pending_settlement_as_paid_and_undo_it(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_settlement']);
        $member = $this->addMember($ctx);

        $this->createExpense($ctx, 100.00, $ctx['user_id']);

        $before = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/settlement", ['Authorization' => "Bearer {$ctx['token']}"])
            ->json('data');
        $this->assertCount(1, $before['pending_settlements']);
        $this->assertSame(50, $before['pending_settlements'][0]['amount']);

        $payment = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/settlement-payments", [
            'from_user_id' => $member['user_id'],
            'to_user_id' => $ctx['user_id'],
            'amount' => 50.00,
        ], ['Authorization' => "Bearer {$member['token']}"])
            ->assertStatus(201)
            ->json('data');

        $after = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/settlement", ['Authorization' => "Bearer {$ctx['token']}"])
            ->json('data');
        $this->assertCount(0, $after['pending_settlements']);
        $this->assertCount(1, $after['settled_payments']);

        // El gasto original no cambia.
        $expenses = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/expenses?year=".now()->year."&month=".now()->month, [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $expenses->assertStatus(200)->assertJsonPath('meta.total_amount', 100);

        // Deshacer: vuelve a aparecer en pending_settlements.
        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/settlement-payments/{$payment['id']}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->assertStatus(204);

        $restored = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/settlement", ['Authorization' => "Bearer {$ctx['token']}"])
            ->json('data');
        $this->assertCount(1, $restored['pending_settlements']);
        $this->assertCount(0, $restored['settled_payments']);
    }

    public function test_settlement_payment_cannot_be_registered_to_oneself(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_settlement']);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/settlement-payments", [
            'from_user_id' => $ctx['user_id'],
            'to_user_id' => $ctx['user_id'],
            'amount' => 10.00,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(422);
    }

    public function test_settlement_payment_mutations_are_audited(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_settlement']);
        $member = $this->addMember($ctx);

        $payment = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/settlement-payments", [
            'from_user_id' => $member['user_id'],
            'to_user_id' => $ctx['user_id'],
            'amount' => 25.00,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201)->json('data');

        $this->assertDatabaseHas('audit_logs', [
            'workspace_id' => $ctx['workspace_id'],
            'entity_type' => 'settlement_payment',
            'entity_id' => $payment['id'],
            'action' => 'created',
        ]);

        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/settlement-payments/{$payment['id']}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'workspace_id' => $ctx['workspace_id'],
            'entity_type' => 'settlement_payment',
            'entity_id' => $payment['id'],
            'action' => 'deleted',
        ]);
    }
}
