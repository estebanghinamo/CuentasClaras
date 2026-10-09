<?php

namespace Tests\Feature\Finance;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class InstallmentTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'savings_goal_movements', 'savings_goals', 'savings_movements', 'savings_wallet',
            'installment_payments', 'installments', 'monthly_closings', 'expenses', 'income_entries',
            'categories', 'workspace_invitations', 'workspace_users', 'workspaces',
            'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function createInstallment(array $ctx, array $overrides = []): array
    {
        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/installments", array_merge([
            'description' => 'Notebook',
            'total_amount' => 100,
            'installments_count' => 3,
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'category_id' => null,
        ], $overrides), ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(201);

        return $response->json('data');
    }

    public function test_create_generates_consecutive_payments_with_exact_total(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $installment = $this->createInstallment($ctx);

        $this->assertSame(3, (int) $installment['installments_count']);
        $this->assertSame(33.33, (float) $installment['installment_amount']);
        $this->assertDatabaseCount('installment_payments', 3);
        $this->assertEquals(100.0, (float) DB::table('installment_payments')->sum('amount'));
    }

    public function test_paying_last_payment_completes_and_unpay_reactivates(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $installment = $this->createInstallment($ctx, ['total_amount' => 100, 'installments_count' => 1]);
        $paymentId = DB::table('installment_payments')->where('installment_id', $installment['id'])->value('id');
        $url = "/api/workspaces/{$ctx['workspace_id']}/installments/{$installment['id']}/payments/{$paymentId}";

        $this->postJson("{$url}/pay", ['paid_at' => now()->format('Y-m-d')], [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->assertStatus(200)->assertJsonPath('data.status', 'paid');
        $this->assertDatabaseHas('installments', ['id' => $installment['id'], 'status' => 'completed']);

        $this->postJson("{$url}/unpay", [], ['Authorization' => "Bearer {$ctx['token']}"],)
            ->assertStatus(200)->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('installments', ['id' => $installment['id'], 'status' => 'active']);
    }

    public function test_cannot_pay_out_of_order_and_cannot_unpay_with_paid_successor(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $installment = $this->createInstallment($ctx, ['total_amount' => 300, 'installments_count' => 3]);
        $payments = DB::table('installment_payments')->where('installment_id', $installment['id'])->orderBy('number')->pluck('id', 'number');
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $url = fn (int $number) => "/api/workspaces/{$ctx['workspace_id']}/installments/{$installment['id']}/payments/{$payments[$number]}";

        // No se puede pagar la 2 ni la 3 sin haber pagado antes la 1.
        $this->postJson("{$url(2)}/pay", [], $auth)->assertStatus(422);
        $this->postJson("{$url(3)}/pay", [], $auth)->assertStatus(422);

        $this->postJson("{$url(1)}/pay", [], $auth)->assertStatus(200);
        $this->postJson("{$url(3)}/pay", [], $auth)->assertStatus(422);
        $this->postJson("{$url(2)}/pay", [], $auth)->assertStatus(200);
        $this->postJson("{$url(3)}/pay", [], $auth)->assertStatus(200);

        // Tampoco se puede deshacer la 1 mientras la 2 y la 3 sigan pagadas.
        $this->postJson("{$url(1)}/unpay", [], $auth)->assertStatus(422);
        $this->postJson("{$url(3)}/unpay", [], $auth)->assertStatus(200);
        $this->postJson("{$url(1)}/unpay", [], $auth)->assertStatus(422);
        $this->postJson("{$url(2)}/unpay", [], $auth)->assertStatus(200);
        $this->postJson("{$url(1)}/unpay", [], $auth)->assertStatus(200);
    }

    public function test_cancel_keeps_paid_payments_and_removes_pending_payments(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $installment = $this->createInstallment($ctx);
        $paymentId = DB::table('installment_payments')->where('installment_id', $installment['id'])->orderBy('number')->value('id');
        $paymentUrl = "/api/workspaces/{$ctx['workspace_id']}/installments/{$installment['id']}/payments/{$paymentId}";

        $this->postJson("{$paymentUrl}/pay", [], ['Authorization' => "Bearer {$ctx['token']}" ])->assertStatus(200);
        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/installments/{$installment['id']}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->assertStatus(204);

        $this->assertDatabaseHas('installments', ['id' => $installment['id'], 'status' => 'cancelled']);
        $this->assertDatabaseHas('installment_payments', ['installment_id' => $installment['id'], 'status' => 'paid']);
        $this->assertDatabaseMissing('installment_payments', ['installment_id' => $installment['id'], 'status' => 'pending']);
    }

    public function test_installments_keep_independent_names_and_payment_details(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $auto = $this->createInstallment($ctx, [
            'description' => 'Compra auto',
            'total_amount' => 2000000,
            'installments_count' => 12,
        ]);
        $phone = $this->createInstallment($ctx, [
            'description' => 'Compra celular',
            'total_amount' => 1500000,
            'installments_count' => 9,
        ]);

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/installments/{$auto['id']}", [
            'description' => 'Compra automovil',
            'category_id' => null,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(200);

        $autoDetail = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/installments/{$auto['id']}", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $phoneDetail = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/installments/{$phone['id']}", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $autoDetail->assertStatus(200)
            ->assertJsonPath('data.description', 'Compra automovil')
            ->assertJsonCount(12, 'data.payments');
        $phoneDetail->assertStatus(200)
            ->assertJsonPath('data.description', 'Compra celular')
            ->assertJsonCount(9, 'data.payments');
    }
}
