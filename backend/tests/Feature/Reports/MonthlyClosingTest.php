<?php

namespace Tests\Feature\Reports;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class MonthlyClosingTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'notifications', 'audit_logs', 'financial_health_scores', 'savings_goal_movements', 'savings_goals',
            'savings_movements', 'savings_wallet', 'installment_payments', 'installments', 'monthly_closings',
            'expenses', 'income_entries', 'budgets', 'categories', 'workspace_invitations', 'workspace_users',
            'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function seedLastMonthIncome(array $ctx, float $amount): \Carbon\CarbonInterface
    {
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(4);

        DB::table('income_entries')->insert([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => $amount,
            'concept' => 'Sueldo',
            'date' => $lastMonth->format('Y-m-d'),
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
        ]);

        return $lastMonth;
    }

    public function test_closings_run_creates_exactly_one_closing_and_is_idempotent(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = $this->seedLastMonthIncome($ctx, 1000);

        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();
        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $this->assertSame(1, DB::table('monthly_closings')
            ->where('workspace_id', $ctx['workspace_id'])
            ->where('year', $lastMonth->year)->where('month', $lastMonth->month)
            ->count());
    }

    public function test_closings_run_rejects_current_or_future_period(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $current = now();

        $this->artisan('closings:run', [
            '--workspace' => $ctx['workspace_id'], '--year' => $current->year, '--month' => $current->month,
        ])->assertFailed();
    }

    public function test_expense_on_closed_month_is_rejected_and_delete_does_not_change_closing(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = $this->seedLastMonthIncome($ctx, 1000);

        DB::table('expenses')->insert([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => 200,
            'description' => 'Antes de cerrar',
            'date' => $lastMonth->format('Y-m-d'),
        ]);

        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $blocked = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/expenses", [
            'amount' => 50, 'category_id' => null, 'payment_method' => 'cash', 'date' => $lastMonth->format('Y-m-d'),
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $blocked->assertStatus(422)->assertJsonPath('error.code', 'MONTH_CLOSED');

        $before = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}", [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->json('data');

        DB::table('expenses')->where('workspace_id', $ctx['workspace_id'])->delete();

        $after = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}", [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->json('data');

        $this->assertSame($before['total_expenses'], $after['total_expenses']);
    }

    public function test_closing_with_negative_remaining_is_not_applicable(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = $this->seedLastMonthIncome($ctx, 100);
        DB::table('expenses')->insert([
            'workspace_id' => $ctx['workspace_id'], 'user_id' => $ctx['user_id'],
            'amount' => 500, 'description' => 'Gasto grande', 'date' => $lastMonth->format('Y-m-d'),
        ]);

        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.allocation_status', 'not_applicable')
            ->assertJsonPath('data.remaining_amount', -400);
    }

    public function test_allocate_splits_between_wallet_and_goal_and_respects_cap(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = $this->seedLastMonthIncome($ctx, 1000);
        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $goalId = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/savings/goals", [
            'name' => 'Vacaciones', 'target_amount' => 5000,
        ], ['Authorization' => "Bearer {$ctx['token']}"])->json('data.id');

        $response = $this->postJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}/allocate",
            ['to_wallet' => 300, 'to_goals' => [['goal_id' => $goalId, 'amount' => 200]], 'to_next_month' => 0],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.allocated_to_wallet', 300)
            ->assertJsonPath('data.allocated_to_goals', 200)
            ->assertJsonPath('data.allocation_status', 'pending')
            ->assertJsonPath('data.unallocated_amount', 500);

        $tooMuch = $this->postJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}/allocate",
            ['to_wallet' => 600, 'to_goals' => [], 'to_next_month' => 0],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );
        $tooMuch->assertStatus(422)->assertJsonPath('error.code', 'INSUFFICIENT_FUNDS');

        $rest = $this->postJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}/allocate",
            ['to_wallet' => 500, 'to_goals' => [], 'to_next_month' => 0],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );
        $rest->assertStatus(200)
            ->assertJsonPath('data.allocation_status', 'allocated')
            ->assertJsonPath('data.unallocated_amount', 0);
    }

    public function test_allocate_on_not_applicable_month_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = $this->seedLastMonthIncome($ctx, 100);
        DB::table('expenses')->insert([
            'workspace_id' => $ctx['workspace_id'], 'user_id' => $ctx['user_id'],
            'amount' => 500, 'description' => 'Gasto grande', 'date' => $lastMonth->format('Y-m-d'),
        ]);
        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $response = $this->postJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}/allocate",
            ['to_wallet' => 10, 'to_goals' => [], 'to_next_month' => 0],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_allocate_to_next_month_creates_income_entry_in_that_period(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = $this->seedLastMonthIncome($ctx, 1000);
        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $response = $this->postJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings/{$lastMonth->year}/{$lastMonth->month}/allocate",
            ['to_wallet' => 0, 'to_goals' => [], 'to_next_month' => 400],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.allocated_to_next_month', 400)
            ->assertJsonPath('data.allocation_status', 'pending')
            ->assertJsonPath('data.unallocated_amount', 600);

        $nextMonth = $lastMonth->copy()->addMonthNoOverflow();
        $closingId = DB::table('monthly_closings')
            ->where('workspace_id', $ctx['workspace_id'])
            ->where('year', $lastMonth->year)->where('month', $lastMonth->month)
            ->value('id');
        $this->assertDatabaseHas('income_entries', [
            'workspace_id' => $ctx['workspace_id'],
            'year' => $nextMonth->year,
            'month' => $nextMonth->month,
            'amount' => 400,
            'source' => 'closing',
            'closing_id' => $closingId,
        ]);
    }

    public function test_allocate_to_next_month_rejected_when_next_month_already_closed(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $twoMonthsAgo = now()->subMonthsNoOverflow(2)->startOfMonth()->addDays(4);
        DB::table('income_entries')->insert([
            'workspace_id' => $ctx['workspace_id'], 'user_id' => $ctx['user_id'],
            'amount' => 1000, 'concept' => 'Sueldo', 'date' => $twoMonthsAgo->format('Y-m-d'),
            'year' => $twoMonthsAgo->year, 'month' => $twoMonthsAgo->month,
        ]);
        $lastMonth = $this->seedLastMonthIncome($ctx, 1000);

        $this->artisan('closings:run', [
            '--workspace' => $ctx['workspace_id'], '--year' => $twoMonthsAgo->year, '--month' => $twoMonthsAgo->month,
        ])->assertSuccessful();
        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $before = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings/{$twoMonthsAgo->year}/{$twoMonthsAgo->month}",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );
        $before->assertJsonPath('data.next_month_open', false);

        $response = $this->postJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings/{$twoMonthsAgo->year}/{$twoMonthsAgo->month}/allocate",
            ['to_wallet' => 0, 'to_goals' => [], 'to_next_month' => 100],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_get_unknown_closing_returns_not_found(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/closings/2020/1", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(404)->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_month_closed_notification_is_created_for_each_member(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_separate']);
        $member = $this->registerUser();
        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);
        $this->seedLastMonthIncome($ctx, 1000);

        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $this->assertSame(2, DB::table('notifications')->where('type', 'month_closed')->count());
    }

    public function test_index_lists_closed_months(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $lastMonth = $this->seedLastMonthIncome($ctx, 1000);
        $this->artisan('closings:run', ['--workspace' => $ctx['workspace_id']])->assertSuccessful();

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/closings",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200);
        $closings = $response->json('data');
        $this->assertCount(1, $closings);
        $this->assertSame($lastMonth->year, $closings[0]['year']);
        $this->assertSame($lastMonth->month, $closings[0]['month']);
    }
}
