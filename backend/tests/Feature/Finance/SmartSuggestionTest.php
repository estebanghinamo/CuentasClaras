<?php

namespace Tests\Feature\Finance;

use App\Services\Finance\SmartSuggestionService;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class SmartSuggestionTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'notifications', 'audit_logs', 'smart_suggestions', 'service_payments', 'services', 'expenses',
            'categories', 'savings_wallet', 'workspace_invitations', 'workspace_users', 'workspaces',
            'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function seedRecurringExpense(array $ctx, string $description, float $amount, int $monthsAgo): void
    {
        $date = now()->subMonthsNoOverflow($monthsAgo)->startOfMonth()->addDays(4);

        DB::table('expenses')->insert([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => $amount,
            'description' => $description,
            'payment_method' => 'other',
            'date' => $date->format('Y-m-d'),
        ]);
    }

    public function test_scan_detects_a_recurring_expense_as_a_suggestion(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->seedRecurringExpense($ctx, 'Spotify 09/2026', 2000, 0);
        $this->seedRecurringExpense($ctx, 'Spotify 08/2026', 2050, 1);
        $this->seedRecurringExpense($ctx, 'Spotify 07/2026', 1980, 2);

        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/suggestions?status=pending", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertSame(3, DB::table('smart_suggestions')->value('occurrences'));
    }

    public function test_scan_does_not_suggest_when_a_matching_service_already_exists(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/services", [
            'name' => 'Spotify', 'amount' => 2000, 'is_estimated' => false,
            'due_day_start' => 10, 'late_fee_type' => 'fixed', 'late_fee_value' => 0,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $this->seedRecurringExpense($ctx, 'Spotify 09/2026', 2000, 0);
        $this->seedRecurringExpense($ctx, 'Spotify 08/2026', 2050, 1);
        $this->seedRecurringExpense($ctx, 'Spotify 07/2026', 1980, 2);

        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);

        $this->assertSame(0, DB::table('smart_suggestions')->count());
    }

    public function test_amounts_outside_tolerance_do_not_qualify(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->seedRecurringExpense($ctx, 'Gimnasio 09/2026', 1000, 0);
        $this->seedRecurringExpense($ctx, 'Gimnasio 08/2026', 1100, 1);
        $this->seedRecurringExpense($ctx, 'Gimnasio 07/2026', 1300, 2);

        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);

        $this->assertSame(0, DB::table('smart_suggestions')->count());
    }

    public function test_accept_creates_service_and_is_not_proposed_again(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->seedRecurringExpense($ctx, 'Netflix 09/2026', 3000, 0);
        $this->seedRecurringExpense($ctx, 'Netflix 08/2026', 3000, 1);
        $this->seedRecurringExpense($ctx, 'Netflix 07/2026', 3000, 2);
        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);

        $suggestionId = DB::table('smart_suggestions')->value('id');

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/suggestions/{$suggestionId}/accept", [
            'name' => 'Netflix', 'amount' => 3000, 'is_estimated' => false,
            'due_day_start' => 5, 'late_fee_type' => 'fixed', 'late_fee_value' => 0,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(201)
            ->assertJsonPath('data.suggestion.status', 'accepted')
            ->assertJsonPath('data.service.name', 'Netflix');

        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);
        $this->assertSame(1, DB::table('smart_suggestions')->count());
    }

    public function test_dismiss_marks_dismissed_and_is_not_proposed_again(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->seedRecurringExpense($ctx, 'Gym 09/2026', 1500, 0);
        $this->seedRecurringExpense($ctx, 'Gym 08/2026', 1500, 1);
        $this->seedRecurringExpense($ctx, 'Gym 07/2026', 1500, 2);
        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);

        $suggestionId = DB::table('smart_suggestions')->value('id');

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/suggestions/{$suggestionId}/dismiss", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $response->assertStatus(200)->assertJsonPath('data.status', 'dismissed');

        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);
        $this->assertSame(1, DB::table('smart_suggestions')->count());
        $this->assertSame('dismissed', DB::table('smart_suggestions')->value('status'));
    }

    public function test_notification_is_created_when_a_new_suggestion_appears(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->seedRecurringExpense($ctx, 'Seguro 09/2026', 5000, 0);
        $this->seedRecurringExpense($ctx, 'Seguro 08/2026', 5000, 1);
        $this->seedRecurringExpense($ctx, 'Seguro 07/2026', 5000, 2);

        app(SmartSuggestionService::class)->scan($ctx['workspace_id']);

        $this->assertSame(1, DB::table('notifications')->where('type', 'smart_suggestion')->count());
    }
}
