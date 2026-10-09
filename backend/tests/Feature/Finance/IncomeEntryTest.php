<?php

namespace Tests\Feature\Finance;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class IncomeEntryTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'income_entries', 'monthly_closings', 'categories', 'savings_wallet',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function currentPeriodDate(int $day = 10): string
    {
        return now()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
    }

    public function test_two_entries_in_the_same_month_sum_in_total(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 500.50, 'concept' => 'Extra', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/income-entries?year=".now()->year."&month=".now()->month,
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200)->assertJsonPath('data.total', 1500.5)->assertJsonCount(2, 'data.entries');
    }

    public function test_by_user_groups_correctly_in_shared_workspace(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);

        $date = $this->currentPeriodDate();
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo owner', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 2000, 'concept' => 'Sueldo member', 'date' => $date,
        ], ['Authorization' => "Bearer {$member['token']}"]);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/income-entries?year=".now()->year."&month=".now()->month,
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200)->assertJsonPath('data.total', 3000)->assertJsonCount(2, 'data.by_user');
    }

    public function test_month_closed_rejects_create_and_update(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        DB::table('monthly_closings')->insert([
            'workspace_id' => $ctx['workspace_id'], 'year' => now()->year, 'month' => now()->month,
            'total_income' => 0, 'total_expenses' => 0, 'total_services' => 0, 'total_installments' => 0,
            'savings_generated' => 0, 'remaining_amount' => 0, 'allocation_status' => 'not_applicable',
        ]);

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $create->assertStatus(422)->assertJsonPath('error.code', 'MONTH_CLOSED');
    }

    public function test_delete_of_closed_month_entry_works_and_does_not_alter_closing(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        $entry = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $entryId = $entry->json('data.id');

        DB::table('monthly_closings')->insert([
            'workspace_id' => $ctx['workspace_id'], 'year' => now()->year, 'month' => now()->month,
            'total_income' => 1000, 'total_expenses' => 0, 'total_services' => 0, 'total_installments' => 0,
            'savings_generated' => 0, 'remaining_amount' => 1000, 'allocation_status' => 'not_applicable',
        ]);

        $delete = $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/income-entries/{$entryId}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $delete->assertStatus(204);
        $this->assertDatabaseMissing('income_entries', ['id' => $entryId]);
        $this->assertDatabaseHas('monthly_closings', ['workspace_id' => $ctx['workspace_id'], 'total_income' => 1000]);
    }

    public function test_shared_separate_member_cannot_edit_others_income(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_separate']);
        $member = $this->registerUser();
        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);

        $date = $this->currentPeriodDate();
        $entry = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo owner', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $entryId = $entry->json('data.id');

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/income-entries/{$entryId}", [
            'amount' => 5000, 'concept' => 'Hackeado', 'date' => $date,
        ], ['Authorization' => "Bearer {$member['token']}"]);

        $update->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_shared_joint_member_can_edit_others_income(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_joint']);
        $member = $this->registerUser();
        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);

        $date = $this->currentPeriodDate();
        $entry = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Sueldo owner', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $entryId = $entry->json('data.id');

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/income-entries/{$entryId}", [
            'amount' => 5000, 'concept' => 'Editado por member', 'date' => $date,
        ], ['Authorization' => "Bearer {$member['token']}"]);

        $update->assertStatus(200)->assertJsonPath('data.concept', 'Editado por member');
    }

    public function test_date_too_far_in_the_future_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $farDate = now()->addMonths(3)->format('Y-m-d');

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/income-entries", [
            'amount' => 1000, 'concept' => 'Muy futuro', 'date' => $farDate,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_closing_allocation_entry_cannot_be_updated_or_deleted(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        $entryId = DB::table('income_entries')->insertGetId([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => 889462,
            'concept' => 'Sobrante del cierre 08/2026',
            'date' => $date,
            'year' => now()->year,
            'month' => now()->month,
            'source' => 'closing',
        ]);

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/income-entries/{$entryId}", [
            'amount' => 1, 'concept' => 'Hackeado', 'date' => $date,
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $update->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');

        $delete = $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/income-entries/{$entryId}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $delete->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');

        $this->assertDatabaseHas('income_entries', ['id' => $entryId, 'amount' => 889462]);
    }

    public function test_list_and_export_expose_source_of_each_entry(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $date = $this->currentPeriodDate();

        DB::table('income_entries')->insert([
            'workspace_id' => $ctx['workspace_id'],
            'user_id' => $ctx['user_id'],
            'amount' => 889462,
            'concept' => 'Sobrante del cierre 08/2026',
            'date' => $date,
            'year' => now()->year,
            'month' => now()->month,
            'source' => 'closing',
        ]);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/income-entries?year=".now()->year."&month=".now()->month,
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200)->assertJsonPath('data.entries.0.source', 'closing');
    }
}
