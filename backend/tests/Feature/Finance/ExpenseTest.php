<?php

namespace Tests\Feature\Finance;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'expenses', 'monthly_closings', 'categories', 'savings_wallet',
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

    public function test_create_update_and_delete_expense(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $categoryId = $this->createCategory($ctx);
        $date = $this->currentPeriodDate();

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 500, 'category_id' => $categoryId, 'description' => 'Super', 'payment_method' => 'debit', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $create->assertStatus(201)->assertJsonPath('data.category_name', 'Comida');
        $expenseId = $create->json('data.id');

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/expenses/{$expenseId}", [
            'amount' => 600, 'category_id' => null, 'description' => 'Super editado', 'payment_method' => 'credit', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $update->assertStatus(200)->assertJsonPath('data.amount', 600)->assertJsonPath('data.category_id', null);

        $delete = $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/expenses/{$expenseId}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $delete->assertStatus(204);
        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
    }

    public function test_category_from_another_workspace_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $other = $this->createWorkspaceAsOwner(userOverrides: ['email' => 'other-owner@example.com']);
        $foreignCategoryId = $this->createCategory($other);

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => $foreignCategoryId, 'payment_method' => 'cash', 'date' => $this->currentPeriodDate(),
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)->assertJsonStructure(['error' => ['details' => ['category_id']]]);
    }

    public function test_combined_filters_return_correct_results_and_total_amount(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $food = $this->createCategory($ctx, 'Comida');
        $transport = $this->createCategory($ctx, 'Transporte');
        $date = $this->currentPeriodDate();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 500, 'category_id' => $food, 'description' => 'Supermercado', 'payment_method' => 'debit', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => $transport, 'description' => 'Colectivo', 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 50, 'category_id' => null, 'payment_method' => 'other', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/expenses?category_ids[]={$food}&category_ids[]={$transport}&amount_min=80&amount_max=1000&search=super",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.description', 'Supermercado')
            ->assertJsonPath('meta.total_amount', 500);
    }

    public function test_pagination_meta_is_correct(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        foreach (range(1, 5) as $i) {
            $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
                'amount' => $i * 10, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
            ], ['Authorization' => "Bearer {$ctx['token']}"]);
        }

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/expenses?per_page=2", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)->assertJsonPath('meta.last_page', 3);
    }

    public function test_month_closed_rejects_create_but_allows_delete(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        $entry = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $expenseId = $entry->json('data.id');

        DB::table('monthly_closings')->insert([
            'workspace_id' => $ctx['workspace_id'], 'year' => now()->year, 'month' => now()->month,
            'total_income' => 0, 'total_expenses' => 100, 'total_services' => 0, 'total_installments' => 0,
            'savings_generated' => 0, 'remaining_amount' => -100, 'allocation_status' => 'not_applicable',
        ]);

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $create->assertStatus(422)->assertJsonPath('error.code', 'MONTH_CLOSED');

        $delete = $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/expenses/{$expenseId}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $delete->assertStatus(204);
        $this->assertDatabaseHas('monthly_closings', ['workspace_id' => $ctx['workspace_id'], 'total_expenses' => 100]);
    }

    public function test_permissions_across_the_three_workspace_types(): void
    {
        $date = $this->currentPeriodDate();

        // shared_separate: member no puede editar el gasto del owner.
        $separate = $this->createWorkspaceAsOwner(['type' => 'shared_separate']);
        $separateMember = $this->registerUser();
        $invite = $this->postJson("/api/workspaces/{$separate['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$separate['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$separateMember['token']}"]);
        $separateExpense = $this->postJson("/api/workspaces/{$separate['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$separate['token']}"])->json('data.id');
        $this->putJson("/api/workspaces/{$separate['workspace_id']}/expenses/{$separateExpense}", [
            'amount' => 999, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$separateMember['token']}"])->assertStatus(403);

        // shared_joint: member SÍ puede editar el gasto del owner.
        $joint = $this->createWorkspaceAsOwner(['type' => 'shared_joint'], ['email' => 'joint-owner@example.com']);
        $jointMember = $this->registerUser(['email' => 'joint-member@example.com']);
        $jointInvite = $this->postJson("/api/workspaces/{$joint['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$joint['token']}"]);
        $this->postJson("/api/invitations/{$jointInvite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$jointMember['token']}"]);
        $jointExpense = $this->postJson("/api/workspaces/{$joint['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$joint['token']}"])->json('data.id');
        $this->putJson("/api/workspaces/{$joint['workspace_id']}/expenses/{$jointExpense}", [
            'amount' => 999, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$jointMember['token']}"])->assertStatus(200);

        // individual: el owner siempre puede editar sus propios gastos.
        $individual = $this->createWorkspaceAsOwner(['type' => 'individual'], ['email' => 'individual-owner@example.com']);
        $individualExpense = $this->postJson("/api/workspaces/{$individual['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$individual['token']}"])->json('data.id');
        $this->putJson("/api/workspaces/{$individual['workspace_id']}/expenses/{$individualExpense}", [
            'amount' => 999, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$individual['token']}"])->assertStatus(200);
    }

    public function test_empty_results_return_zero_totals(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)->assertJsonPath('meta.total_amount', 0);
    }

    public function test_payment_methods_lists_only_distinct_methods_actually_used(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 100, 'category_id' => null, 'payment_method' => 'cash', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 200, 'category_id' => null, 'payment_method' => 'debit', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 300, 'category_id' => null, 'payment_method' => 'debit', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/expenses/payment-methods",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200);
        $this->assertEqualsCanonicalizing(['cash', 'debit'], $response->json('data'));
    }
}
