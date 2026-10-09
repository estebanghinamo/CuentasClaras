<?php

namespace Tests\Feature\Workspace;

use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'categories', 'savings_wallet', 'workspace_invitations',
            'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    public function test_create_workspace_creates_membership_and_wallet_with_no_default_categories(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $this->assertDatabaseHas('workspace_users', ['workspace_id' => $ctx['workspace_id'], 'user_id' => $ctx['user_id'], 'role' => 'owner']);
        $this->assertDatabaseHas('savings_wallet', ['workspace_id' => $ctx['workspace_id'], 'balance' => 0]);
        $this->assertSame(0, DB::table('categories')->where('workspace_id', $ctx['workspace_id'])->count());
    }

    public function test_workspace_list_returns_only_own_workspaces(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson('/api/workspaces', ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ctx['workspace_id']);
    }

    public function test_member_gets_forbidden_on_update_and_delete(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();

        $this->acceptInvitation($ctx, $member);

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}", [
            'name' => 'Nuevo nombre', 'currency' => 'ARS', 'type' => 'shared_joint',
        ], ['Authorization' => "Bearer {$member['token']}"]);
        $update->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');

        $delete = $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}", [], ['Authorization' => "Bearer {$member['token']}"]);
        $delete->assertStatus(403);
    }

    public function test_non_member_gets_not_found_instead_of_forbidden(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $stranger = $this->registerUser();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}", ['Authorization' => "Bearer {$stranger['token']}"]);

        $response->assertStatus(404);
    }

    public function test_owner_cannot_leave_or_be_removed(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/leave", [], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_member_can_leave_workspace(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $this->acceptInvitation($ctx, $member);

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/leave", [], ['Authorization' => "Bearer {$member['token']}"]);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('workspace_users', ['workspace_id' => $ctx['workspace_id'], 'user_id' => $member['user_id']]);
    }

    public function test_switching_to_individual_with_two_members_fails(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $this->acceptInvitation($ctx, $member);

        $response = $this->putJson("/api/workspaces/{$ctx['workspace_id']}", [
            'name' => 'Casa', 'currency' => 'ARS', 'type' => 'individual',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_complete_onboarding_marks_workspace(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/onboarding/complete", [], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(200)->assertJsonPath('data.onboarding_completed', true);
    }

    private function acceptInvitation(array $ctx, array $member): void
    {
        $invitation = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->json('data.code');

        $this->postJson("/api/invitations/{$invitation}/accept", [], ['Authorization' => "Bearer {$member['token']}"])
            ->assertStatus(200);
    }
}
