<?php

namespace Tests\Feature\Workspace;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

/** WorkspaceMemberController::index/destroy - leave y onboarding/complete ya cubiertos en WorkspaceTest. */
class WorkspaceMemberTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'categories', 'notifications', 'notification_preferences', 'savings_wallet',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    private function acceptInvitation(array $ctx, array $member): void
    {
        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $this->postJson(
            "/api/invitations/{$invite->json('data.code')}/accept",
            [],
            ['Authorization' => "Bearer {$member['token']}"],
        )->assertStatus(200);
    }

    public function test_index_lists_owner_and_members(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $this->acceptInvitation($ctx, $member);

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/members",
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(200);
        $roles = array_column($response->json('data'), 'role');
        $this->assertContains('owner', $roles);
        $this->assertContains('member', $roles);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_non_member_cannot_list_members(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $outsider = $this->registerUser();

        $response = $this->getJson(
            "/api/workspaces/{$ctx['workspace_id']}/members",
            ['Authorization' => "Bearer {$outsider['token']}"],
        );

        $response->assertStatus(404);
    }

    public function test_owner_can_remove_a_member(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $this->acceptInvitation($ctx, $member);

        $response = $this->deleteJson(
            "/api/workspaces/{$ctx['workspace_id']}/members/{$member['user_id']}",
            [],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(204);
        $this->assertDatabaseMissing('workspace_users', [
            'workspace_id' => $ctx['workspace_id'], 'user_id' => $member['user_id'],
        ]);
    }

    public function test_member_cannot_remove_another_member(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $this->acceptInvitation($ctx, $member);
        $other = $this->registerUser();
        $this->acceptInvitation($ctx, $other);

        $response = $this->deleteJson(
            "/api/workspaces/{$ctx['workspace_id']}/members/{$other['user_id']}",
            [],
            ['Authorization' => "Bearer {$member['token']}"],
        );

        $response->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_owner_cannot_remove_themselves_via_destroy(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->deleteJson(
            "/api/workspaces/{$ctx['workspace_id']}/members/{$ctx['user_id']}",
            [],
            ['Authorization' => "Bearer {$ctx['token']}"],
        );

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }
}
