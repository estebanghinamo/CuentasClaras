<?php

namespace Tests\Feature\Finance;

use App\Repositories\ServicePaymentRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'savings_goal_movements', 'savings_goals', 'savings_movements', 'savings_wallet',
            'service_payments', 'services', 'monthly_closings', 'categories',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function createService(array $ctx, array $overrides = []): array
    {
        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/services", array_merge([
            'name' => 'Luz', 'amount' => 15000, 'is_estimated' => false,
            'due_day_start' => 10, 'due_day_end' => 15,
            'late_fee_type' => 'percentage', 'late_fee_value' => 5,
        ], $overrides), ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(201);

        return $response->json('data');
    }

    public function test_create_service_generates_current_period_payment(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = $this->createService($ctx);

        $this->assertDatabaseHas('service_payments', [
            'service_id' => $service['id'], 'year' => now()->year, 'month' => now()->month, 'status' => 'pending',
        ]);
    }

    public function test_update_and_delete_service(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = $this->createService($ctx);

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/services/{$service['id']}", [
            'name' => 'Luz editado', 'amount' => 16000, 'is_estimated' => true,
            'due_day_start' => 12, 'due_day_end' => null,
            'late_fee_type' => 'fixed', 'late_fee_value' => 200, 'active' => false,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $update->assertStatus(200)->assertJsonPath('data.name', 'Luz editado')->assertJsonPath('data.active', false);

        $delete = $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/services/{$service['id']}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $delete->assertStatus(204);
        $this->assertDatabaseMissing('services', ['id' => $service['id']]);
        $this->assertDatabaseMissing('service_payments', ['service_id' => $service['id']]);
    }

    public function test_list_any_future_month_generates_rows_but_past_month_does_not(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = $this->createService($ctx);

        $next = now()->addMonthNoOverflow();
        $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/service-payments?year={$next->year}&month={$next->month}",
            ['Authorization' => "Bearer {$ctx['token']}"],
        )->assertStatus(200)->assertJsonCount(1, 'data.items');

        // No solo el mes siguiente: un servicio nuevo tiene que verse al
        // navegar a cualquier mes futuro, no solo current+1.
        $farFuture = now()->addMonthsNoOverflow(5);
        $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/service-payments?year={$farFuture->year}&month={$farFuture->month}",
            ['Authorization' => "Bearer {$ctx['token']}"],
        )->assertStatus(200)->assertJsonCount(1, 'data.items');

        $past = now()->subMonthNoOverflow();
        $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/service-payments?year={$past->year}&month={$past->month}",
            ['Authorization' => "Bearer {$ctx['token']}"],
        )->assertStatus(200)->assertJsonCount(0, 'data.items');

        $this->assertDatabaseMissing('service_payments', ['service_id' => $service['id'], 'year' => $past->year, 'month' => $past->month]);
    }

    public function test_pay_after_due_date_applies_percentage_late_fee_and_marks_was_late(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = $this->createService($ctx, ['due_day_start' => 1, 'due_day_end' => 1, 'late_fee_type' => 'percentage', 'late_fee_value' => 10]);

        // El pago se crea para el mes actual y se mueve al mes ANTERIOR
        // completo por UPDATE directo, para que due_date_end quede
        // inequívocamente en el pasado y el pago se considere tardío. Antes
        // se usaba "el día del mes de ayer" como due_day, pero eso es frágil
        // si hoy es el día 1 del mes (ayer pertenece a OTRO mes, y ese mismo
        // número de día dentro del mes actual puede no haber pasado todavía).
        $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');
        $previousMonth = now()->subMonthNoOverflow();
        DB::table('service_payments')
            ->where('id', $paymentId)
            ->update(['year' => $previousMonth->year, 'month' => $previousMonth->month]);

        $pay = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/pay", [
            'amount_paid' => 1000,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $pay->assertStatus(200)
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.was_late', true)
            ->assertJsonPath('data.late_fee_applied', 100)
            ->assertJsonPath('data.amount_paid', 1100);
    }

    public function test_pay_twice_returns_invalid_state_and_unpay_restores_pending(): void
    {
        // Fecha fija: due_day_end=28 debe seguir "pending" (no vencido) despues del
        // unpay, sin importar en que dia del mes real corra la suite (ver ERROR_LOG.md).
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 12, 0, 0, config('app.timezone')));

        try {
            $ctx = $this->createWorkspaceAsOwner();
            $service = $this->createService($ctx, ['due_day_start' => 1, 'due_day_end' => 28]);
            $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');

            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/pay", [
                'amount_paid' => 15000,
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(200);

            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/pay", [
                'amount_paid' => 15000,
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');

            $unpay = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/unpay", [], [
                'Authorization' => "Bearer {$ctx['token']}",
            ]);
            $unpay->assertStatus(200)->assertJsonPath('data.status', 'pending')->assertJsonPath('data.amount_paid', null);

            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/unpay", [], [
                'Authorization' => "Bearer {$ctx['token']}",
            ])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_paying_a_closed_month_is_allowed_but_unpaying_it_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = $this->createService($ctx, ['due_day_start' => 1, 'due_day_end' => 28]);
        $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');

        DB::table('monthly_closings')->insert([
            'workspace_id' => $ctx['workspace_id'], 'year' => now()->year, 'month' => now()->month,
            'total_income' => 0, 'total_expenses' => 0, 'total_services' => 15000, 'total_installments' => 0,
            'savings_generated' => 0, 'remaining_amount' => -15000, 'allocation_status' => 'not_applicable',
        ]);

        $pay = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/pay", [
            'amount_paid' => 15000,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $pay->assertStatus(200)->assertJsonPath('data.status', 'paid');

        $unpay = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/unpay", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $unpay->assertStatus(422)->assertJsonPath('error.code', 'MONTH_CLOSED');
    }

    public function test_mark_overdue_job_moves_pending_past_due_to_overdue_and_it_appears_in_overdue_endpoint(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $yesterday = max(1, (int) now()->subDay()->day);
        $service = $this->createService($ctx, ['due_day_start' => $yesterday, 'due_day_end' => $yesterday]);
        $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');

        app(ServicePaymentRepository::class)->markOverdue($ctx['workspace_id'], now()->format('Y-m-d'));

        $this->assertDatabaseHas('service_payments', ['id' => $paymentId, 'status' => 'overdue']);

        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/overdue", [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $paymentId);
    }

    public function test_listing_the_current_period_automatically_marks_pending_past_due_as_overdue(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $yesterday = max(1, (int) now()->subDay()->day);
        $service = $this->createService($ctx, ['due_day_start' => $yesterday, 'due_day_end' => $yesterday]);
        $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');

        // Sin llamar a markOverdue a mano: listar el mes actual (o /overdue) debe
        // detectarlo solo, porque todavía no existe el job diario (M-23).
        $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/service-payments?year=" . now()->year . "&month=" . now()->month,
            ['Authorization' => "Bearer {$ctx['token']}"],
        )->assertStatus(200)->assertJsonPath('data.items.0.status', 'overdue');

        $this->assertDatabaseHas('service_payments', ['id' => $paymentId, 'status' => 'overdue']);
    }

    public function test_pay_can_skip_the_calculated_late_fee_and_apply_a_manual_difference(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $yesterday = max(1, (int) now()->subDay()->day);
        $service = $this->createService($ctx, [
            'due_day_start' => $yesterday, 'due_day_end' => $yesterday,
            'late_fee_type' => 'percentage', 'late_fee_value' => 10,
        ]);
        $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');

        $pay = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/pay", [
            'amount_paid' => 1000, 'apply_late_fee' => false, 'fee_difference' => 500,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $pay->assertStatus(200)
            ->assertJsonPath('data.late_fee_applied', 500)
            ->assertJsonPath('data.amount_paid', 1500);
    }

    public function test_shared_separate_member_can_pay_a_service_created_by_the_owner(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_separate']);
        $service = $this->createService($ctx, ['due_day_start' => 1, 'due_day_end' => 28]);
        $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');

        $member = $this->registerUser();
        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/pay", [
            'amount_paid' => 15000,
        ], ['Authorization' => "Bearer {$member['token']}"])->assertStatus(200);
    }

    public function test_percentage_late_fee_over_100_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/services", [
            'name' => 'Luz', 'amount' => 15000, 'is_estimated' => false,
            'due_day_start' => 10, 'due_day_end' => 15,
            'late_fee_type' => 'percentage', 'late_fee_value' => 150,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)->assertJsonStructure(['error' => ['details' => ['late_fee_value']]]);
    }

    public function test_index_filters_by_active_query_param(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = $this->createService($ctx, ['name' => 'Netflix']);

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/services/{$service['id']}", [
            'name' => 'Netflix', 'amount' => 15000, 'is_estimated' => false,
            'due_day_start' => 10, 'due_day_end' => 15,
            'late_fee_type' => 'percentage', 'late_fee_value' => 5, 'active' => false,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(200);

        $activeOnly = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/services?active=true",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );
        $activeOnly->assertStatus(200)->assertJsonCount(0, 'data');

        $inactiveOnly = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/services?active=false",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );
        $inactiveOnly->assertStatus(200)->assertJsonCount(1, 'data');

        $all = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/services?active=all",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );
        $all->assertStatus(200)->assertJsonCount(1, 'data');
    }
}
