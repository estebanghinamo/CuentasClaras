<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\HealthScoreService;
use App\Support\Period;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class HealthScoreTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'financial_health_scores', 'budgets', 'categories', 'income_entries', 'audit_logs',
            'savings_wallet', 'workspace_invitations', 'workspace_users', 'workspaces',
            'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    public function test_score_from_inputs_with_no_budgets_or_services_is_neutral(): void
    {
        $service = app(HealthScoreService::class);

        [$score, $breakdown] = $service->scoreFromInputs([
            'total_income' => 1000,
            'total_expenses' => 500,
            'total_installments' => 0,
            'savings_in_month' => 0,
            'budgets' => [],
            'services' => ['paid_on_time' => 0, 'paid_late' => 0, 'overdue' => 0],
        ]);

        // (70*30 + 0*30 + 100*20 + 100*20) / 100 = 61
        $this->assertSame(61, $score);
        $this->assertSame('Sin presupuestos definidos', $breakdown['budgets']['detail']);
        $this->assertSame('Sin servicios', $breakdown['services_on_time']['detail']);
    }

    public function test_score_from_inputs_penalizes_exceeded_budgets_and_late_services(): void
    {
        $service = app(HealthScoreService::class);

        [$score, $breakdown] = $service->scoreFromInputs([
            'total_income' => 1000,
            'total_expenses' => 900,
            'total_installments' => 500,
            'savings_in_month' => 0,
            'budgets' => [
                ['limit_amount' => 100, 'spent_amount' => 150],
            ],
            'services' => ['paid_on_time' => 0, 'paid_late' => 1, 'overdue' => 1],
        ]);

        $this->assertSame(0, $breakdown['budgets']['score']);
        $this->assertSame(0, $breakdown['budgets']['within_limit_count']);
        $this->assertSame(25, $breakdown['services_on_time']['score']);
        $this->assertSame(0, $breakdown['installments_load']['score']);
        $this->assertLessThan(30, $score);
    }

    public function test_score_from_inputs_full_savings_and_healthy_budgets_scores_high(): void
    {
        $service = app(HealthScoreService::class);

        [$score] = $service->scoreFromInputs([
            'total_income' => 1000,
            'total_expenses' => 400,
            'total_installments' => 50,
            'savings_in_month' => 200,
            'budgets' => [
                ['limit_amount' => 500, 'spent_amount' => 300],
            ],
            'services' => ['paid_on_time' => 3, 'paid_late' => 0, 'overdue' => 0],
        ]);

        $this->assertSame(100, $score);
    }

    public function test_compute_live_returns_null_without_income_or_expenses(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $service = app(HealthScoreService::class);

        $dto = $service->computeLive($ctx['workspace_id'], Period::current());

        $this->assertNull($dto);
    }

    public function test_compute_and_store_then_list_via_api(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $period = Period::current()->previous();
        $service = app(HealthScoreService::class);

        DB::table('income_entries')->insert([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => 1000,
            'concept' => 'Sueldo',
            'date' => sprintf('%04d-%02d-05', $period->year, $period->month),
            'year' => $period->year,
            'month' => $period->month,
        ]);

        $stored = $service->computeAndStore($ctx['workspace_id'], $period);
        $this->assertSame($period->year, $stored->year);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/health-scores?months=3", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.year', $period->year)
            ->assertJsonPath('data.0.month', $period->month)
            ->assertJsonPath('data.0.score', $stored->score);
    }
}
