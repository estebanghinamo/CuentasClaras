<?php

namespace Tests\Feature\Reports;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'notifications', 'audit_logs', 'financial_health_scores', 'savings_goal_movements', 'savings_goals',
            'savings_movements', 'savings_wallet', 'installment_payments', 'installments', 'monthly_closings',
            'expenses', 'income_entries', 'service_payments', 'services', 'categories', 'workspace_invitations',
            'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function currentPeriodDate(int $day = 10): string
    {
        return now()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
    }

    public function test_dashboard_totals_and_by_category_match_loaded_data(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $category = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'name' => 'Comida', 'icon' => 'restaurant', 'color' => '#FF9800',
        ], ['Authorization' => "Bearer {$ctx['token']}"])->json('data.id');
        $date = $this->currentPeriodDate();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 300, 'category_id' => $category, 'payment_method' => 'debit', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.totals.income', 1000)
            ->assertJsonPath('data.totals.expenses', 300)
            ->assertJsonPath('data.totals.available', 700)
            ->assertJsonPath('data.by_category.0.category_name', 'Comida')
            ->assertJsonPath('data.by_category.0.pct', 100);
    }

    public function test_by_user_groups_correctly_and_balance_is_correct(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);
        $date = $this->currentPeriodDate();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo owner', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 200, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$member['token']}"]);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonCount(2, 'data.by_user');
    }

    public function test_comparison_against_previous_month(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(4);
        $thisMonthDate = $this->currentPeriodDate();

        // El servicio de ingresos solo permite crear/editar ingresos del mes
        // actual (IncomeEntryService::assertCurrentPeriod), así que el ingreso
        // del "mes pasado" se siembra directo en la tabla: lo que se prueba acá
        // es la comparación del dashboard, no esa regla de negocio.
        DB::table('income_entries')->insert([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => 1000,
            'concept' => 'Mes pasado',
            'date' => $lastMonth->format('Y-m-d'),
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
        ]);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1500, 'concept' => 'Este mes', 'date' => $thisMonthDate,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.comparison.previous_income', 1000)
            ->assertJsonPath('data.comparison.income_delta_pct', 50);
    }

    public function test_month_without_data_returns_zeroed_dashboard(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard?year=2020&month=1", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.totals.income', 0)
            ->assertJsonPath('data.totals.expenses', 0)
            ->assertJsonPath('data.totals.available', 0)
            ->assertJsonCount(0, 'data.by_category')
            ->assertJsonCount(0, 'data.by_user')
            ->assertJsonPath('data.comparison.income_delta_pct', null);
    }

    public function test_dashboard_lists_overdue_services(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $yesterday = max(1, (int) now()->subDay()->day);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/services", [
            'name' => 'Luz', 'amount' => 5000, 'is_estimated' => false,
            'due_day_start' => $yesterday, 'due_day_end' => $yesterday,
            'late_fee_type' => 'fixed', 'late_fee_value' => 0,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.overdue_services.count', 1)
            ->assertJsonPath('data.overdue_services.items.0.service_name', 'Luz');
    }

    public function test_dashboard_includes_paid_services_in_expenses_and_available(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/services", [
            'name' => 'Internet', 'amount' => 5000, 'is_estimated' => false,
            'due_day_start' => 28, 'due_day_end' => 28,
            'late_fee_type' => 'fixed', 'late_fee_value' => 0,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->json('data');
        $paymentId = DB::table('service_payments')->where('service_id', $service['id'])->value('id');

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/service-payments/{$paymentId}/pay", [
            'amount_paid' => 5000, 'apply_late_fee' => false, 'fee_difference' => 0,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(200);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.totals.expenses', 5000)
            ->assertJsonPath('data.totals.services_paid', 5000)
            ->assertJsonPath('data.totals.available', -5000)
            ->assertJsonPath('data.by_category.0.category_name', 'Servicios pagados')
            ->assertJsonPath('data.by_user.0.expenses', 5000);
    }

    public function test_projection_reflects_expenses_to_date(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $frozenNow = \Carbon\Carbon::create(2026, 6, 15, 12, 0, 0, config('app.timezone'));
        \Carbon\Carbon::setTestNow($frozenNow);

        try {
            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
                'amount' => 5000, 'concept' => 'Sueldo', 'date' => $frozenNow->format('Y-m-d'),
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
                'amount' => 900, 'category_id' => null, 'payment_method' => 'cash',
                'date' => $frozenNow->copy()->day(3)->format('Y-m-d'),
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
                'amount' => 600, 'category_id' => null, 'payment_method' => 'cash',
                'date' => $frozenNow->copy()->day(15)->format('Y-m-d'),
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

            // 1500 gastado en 15 dias = 100/dia; junio tiene 30 dias => proyectado 3000.
            // Disponible proyectado: 5000 - 3000 = 2000 (sin servicios/cuotas).
            $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
                'Authorization' => "Bearer {$ctx['token']}",
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('data.projection.days_elapsed', 15)
                ->assertJsonPath('data.projection.days_in_month', 30)
                ->assertJsonPath('data.projection.daily_average', 100)
                ->assertJsonPath('data.projection.projected_expenses', 3000)
                ->assertJsonPath('data.projection.projected_available', 2000)
                ->assertJsonPath('data.projection.trend', 'on_track');
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_projection_discounts_net_savings_like_available_does(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $frozenNow = \Carbon\Carbon::create(2026, 6, 15, 12, 0, 0, config('app.timezone'));
        \Carbon\Carbon::setTestNow($frozenNow);

        try {
            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
                'amount' => 5000, 'concept' => 'Sueldo', 'date' => $frozenNow->format('Y-m-d'),
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
                'amount' => 1500, 'category_id' => null, 'payment_method' => 'cash',
                'date' => $frozenNow->copy()->day(15)->format('Y-m-d'),
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
                'type' => 'deposit', 'amount' => 800,
            ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

            // 1500 gastado en 15 dias = 100/dia; junio tiene 30 dias => proyectado 3000.
            // Disponible proyectado: 5000 - 3000 - 800 (deposito al monedero) = 1200,
            // igual que totals.available ya descuenta el neto de ahorro.
            $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
                'Authorization' => "Bearer {$ctx['token']}",
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('data.projection.projected_available', 1200);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_projection_is_null_for_a_past_period(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard?year=2020&month=1", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonPath('data.projection', null);
    }

    public function test_history_mixes_closed_and_live_months(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(4);

        DB::table('income_entries')->insert([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => 2000,
            'concept' => 'Sueldo',
            'date' => $lastMonth->format('Y-m-d'),
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
        ]);
        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 3000, 'concept' => 'Este mes', 'date' => $this->currentPeriodDate(),
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard/history?months=2", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.year', $lastMonth->year)
            ->assertJsonPath('data.0.month', $lastMonth->month)
            ->assertJsonPath('data.0.is_closed', true)
            ->assertJsonPath('data.0.income', 2000)
            ->assertJsonPath('data.1.is_closed', false)
            ->assertJsonPath('data.1.income', 3000);
    }

    public function test_history_discounts_net_savings_like_available_does(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 3000, 'concept' => 'Este mes', 'date' => $this->currentPeriodDate(),
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/movements", [
            'type' => 'deposit', 'amount' => 500,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard/history?months=1", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonPath('data.0.available', 2500);
    }

    public function test_non_member_cannot_see_dashboard(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $stranger = $this->registerUser();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/dashboard", [
            'Authorization' => "Bearer {$stranger['token']}",
        ]);

        $response->assertStatus(404);
    }
}
