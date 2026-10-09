<?php

namespace Tests\Feature\Sidebar;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class SidebarSectionTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'workspace_sidebar_sections', 'audit_logs', 'savings_wallet', 'workspace_invitations',
            'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    public function test_new_workspace_has_no_optional_sections_enabled(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonPath('data.sections', []);
    }

    public function test_set_and_list_sections(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $set = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'sections' => ['savings', 'budgets'],
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $set->assertStatus(200)->assertJsonPath('data.sections', ['budgets', 'savings']);

        $list = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $list->assertStatus(200)->assertJsonPath('data.sections', ['budgets', 'savings']);
    }

    public function test_set_replaces_the_full_set_not_merges(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'sections' => ['savings', 'budgets', 'goals'],
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'sections' => ['members'],
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(200)->assertJsonPath('data.sections', ['members']);
    }

    public function test_empty_sections_array_clears_everything(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'sections' => ['savings'],
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'sections' => [],
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(200)->assertJsonPath('data.sections', []);
    }

    public function test_invalid_section_code_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'sections' => ['not_a_real_section'],
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_preferences_are_per_user_within_the_same_workspace(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_separate']);
        $member = $this->registerUser();

        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], [
            'Authorization' => "Bearer {$member['token']}",
        ]);

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'sections' => ['savings'],
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $memberView = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/sidebar-sections", [
            'Authorization' => "Bearer {$member['token']}",
        ]);

        $memberView->assertStatus(200)->assertJsonPath('data.sections', []);
    }
}
