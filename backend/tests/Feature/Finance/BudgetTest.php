<?php

namespace Tests\Feature\Finance;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'budgets', 'expenses', 'monthly_closings', 'categories', 'savings_wallet',
            'notifications', 'notification_preferences',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function currentPeriodDate(int $day = 10): string
    {
        return now()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
    }

    private function createCategory(array $ctx, string $name = 'Comida'): int
    {
        return $this->postJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'name' => $name, 'icon' => 'restaurant', 'color' => '#FF9800',
        ], ['Authorization' => "Bearer {$ctx['token']}"])->json('data.id');
    }

    private function createExpense(array $ctx, int $categoryId, float $amount): int
    {
        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => $amount, 'category_id' => $categoryId, 'payment_method' => 'cash', 'date' => $this->currentPeriodDate(),
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $response->assertStatus(201);

        return $response->json('data.id');
    }

    public function test_upsert_creates_then_updates_the_same_budget(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $category = $this->createCategory($ctx);
        $year = (int) now()->year;
        $month = (int) now()->month;

        $create = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $category, 'year' => $year, 'month' => $month, 'limit_amount' => 1000,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $create->assertStatus(201)->assertJsonPath('data.limit_amount', 1000)->assertJsonPath('data.alert_level', 'none');

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $category, 'year' => $year, 'month' => $month, 'limit_amount' => 2000,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $update->assertStatus(200)->assertJsonPath('data.limit_amount', 2000)->assertJsonPath('data.id', $create->json('data.id'));
    }

    public function test_category_from_another_workspace_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $other = $this->createWorkspaceAsOwner(userOverrides: ['email' => 'other-owner@example.com']);
        $foreignCategory = $this->createCategory($other);

        $response = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $foreignCategory, 'year' => now()->year, 'month' => now()->month, 'limit_amount' => 500,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_alert_level_progresses_with_spending_and_lowers_on_delete(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $category = $this->createCategory($ctx);
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $category, 'year' => now()->year, 'month' => now()->month, 'limit_amount' => 1000,
        ], $auth)->assertStatus(201);

        $this->createExpense($ctx, $category, 850);
        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/budgets", $auth)
            ->assertStatus(200)->assertJsonPath('data.budgets.0.alert_level', 'warning');

        $lastId = $this->createExpense($ctx, $category, 200);
        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/budgets", $auth)
            ->assertStatus(200)->assertJsonPath('data.budgets.0.alert_level', 'exceeded');

        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/expenses/{$lastId}", [], $auth)->assertStatus(204);
        $this->getJson("/api/workspaces/{$ctx['workspace_id']}/budgets", $auth)
            ->assertStatus(200)->assertJsonPath('data.budgets.0.alert_level', 'warning');
    }

    /** M-17 (2026-09-23): subir de nivel notifica a los miembros; bajar de nivel (borrar gasto) no. */
    public function test_alert_level_rising_notifies_members_but_lowering_does_not(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $category = $this->createCategory($ctx);
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $category, 'year' => now()->year, 'month' => now()->month, 'limit_amount' => 1000,
        ], $auth)->assertStatus(201);

        $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 0);

        // 85% del limite -> sube de 'none' a 'warning', debe notificar al owner.
        $lastId = $this->createExpense($ctx, $category, 850);
        $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 1);

        $notification = $this->getJson('/api/notifications', $auth)->json('data.0');
        $this->assertSame('budget_alert', $notification['type']);
        $this->assertSame('warning', $notification['payload']['level']);

        // Borrar el gasto baja el nivel de nuevo a 'none' - no debe generar una notificacion nueva.
        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/expenses/{$lastId}", [], $auth)->assertStatus(204);
        $this->getJson('/api/notifications/unread-count', $auth)->assertJsonPath('data.count', 1);
    }

    public function test_list_includes_categories_without_budget(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $budgeted = $this->createCategory($ctx, 'Comida');
        $unbudgeted = $this->createCategory($ctx, 'Transporte');
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $budgeted, 'year' => now()->year, 'month' => now()->month, 'limit_amount' => 1000,
        ], $auth)->assertStatus(201);
        $this->createExpense($ctx, $unbudgeted, 300);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/budgets", $auth);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.budgets')
            ->assertJsonCount(1, 'data.categories_without_budget')
            ->assertJsonPath('data.categories_without_budget.0.name', 'Transporte')
            ->assertJsonPath('data.categories_without_budget.0.spent_amount', 300);
    }

    public function test_delete_budget(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $category = $this->createCategory($ctx);
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];

        $budgetId = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $category, 'year' => now()->year, 'month' => now()->month, 'limit_amount' => 1000,
        ], $auth)->json('data.id');

        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/budgets/{$budgetId}", [], $auth)->assertStatus(204);
        $this->assertDatabaseMissing('budgets', ['id' => $budgetId]);
    }

    public function test_copy_from_period_respects_overwrite(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $category = $this->createCategory($ctx);
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];
        $fromYear = (int) now()->year;
        $fromMonth = (int) now()->month;
        $to = now()->addMonthNoOverflow();

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $category, 'year' => $fromYear, 'month' => $fromMonth, 'limit_amount' => 1000,
        ], $auth)->assertStatus(201);

        $copy = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/budgets/copy", [
            'from_year' => $fromYear, 'from_month' => $fromMonth,
            'to_year' => $to->year, 'to_month' => $to->month, 'overwrite' => false,
        ], $auth);
        $copy->assertStatus(200)->assertJsonPath('data.copied_count', 1);

        $copyAgain = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/budgets/copy", [
            'from_year' => $fromYear, 'from_month' => $fromMonth,
            'to_year' => $to->year, 'to_month' => $to->month, 'overwrite' => false,
        ], $auth);
        $copyAgain->assertStatus(200)->assertJsonPath('data.copied_count', 0);
    }

    public function test_budget_in_closed_month_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $category = $this->createCategory($ctx);
        $auth = ['Authorization' => "Bearer {$ctx['token']}"];

        DB::table('monthly_closings')->insert([
            'workspace_id' => $ctx['workspace_id'], 'year' => now()->year, 'month' => now()->month,
            'total_income' => 0, 'total_expenses' => 0, 'total_services' => 0, 'total_installments' => 0,
            'savings_generated' => 0, 'remaining_amount' => 0, 'allocation_status' => 'not_applicable',
        ]);

        $response = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/budgets", [
            'category_id' => $category, 'year' => now()->year, 'month' => now()->month, 'limit_amount' => 1000,
        ], $auth);

        $response->assertStatus(422)->assertJsonPath('error.code', 'MONTH_CLOSED');
    }
}
