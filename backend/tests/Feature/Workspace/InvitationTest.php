<?php

namespace Tests\Feature\Workspace;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'categories', 'notifications', 'notification_preferences', 'savings_wallet', 'workspace_invitations',
            'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    public function test_full_invitation_lifecycle_create_preview_accept_and_reuse_fails(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $create->assertStatus(201);
        $code = $create->json('data.code');

        $preview = $this->getJson("/api/invitations/{$code}");
        $preview->assertStatus(200)->assertJsonPath('data.workspace_name', 'Workspace Test');

        $accept = $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);
        $accept->assertStatus(200);

        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $ctx['workspace_id'], 'user_id' => $member['user_id'], 'role' => 'member',
        ]);

        $reuse = $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $reuse->assertStatus(410)->assertJsonPath('error.code', 'INVITATION_INVALID');
    }

    /** M-17 (2026-09-23): el owner recibe un aviso in-app cuando alguien acepta su invitacion. */
    public function test_owner_gets_notified_when_someone_accepts_the_invitation(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();
        $ownerAuth = ['Authorization' => "Bearer {$ctx['token']}"];

        $this->getJson('/api/notifications/unread-count', $ownerAuth)->assertJsonPath('data.count', 0);

        $code = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], $ownerAuth)->json('data.code');
        $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$member['token']}"])
            ->assertStatus(200);

        $this->getJson('/api/notifications/unread-count', $ownerAuth)->assertJsonPath('data.count', 1);
        $notification = $this->getJson('/api/notifications', $ownerAuth)->json('data.0');
        $this->assertSame('workspace_member_joined', $notification['type']);
    }

    public function test_creator_accepting_own_invitation_is_rejected_and_invitation_stays_pending(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $code = $create->json('data.code');

        $selfAccept = $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $selfAccept->assertStatus(409)->assertJsonPath('error.code', 'CONFLICT');

        // La invitacion sigue pendiente - no se "gasto" con el intento del propio creador.
        $member = $this->registerUser();
        $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$member['token']}"])
            ->assertStatus(200);

        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $ctx['workspace_id'], 'user_id' => $member['user_id'], 'role' => 'member',
        ]);
    }

    public function test_existing_member_accepting_another_invitation_to_same_workspace_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();

        $first = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $this->postJson("/api/invitations/{$first->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"])
            ->assertStatus(200);

        $second = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $reAccept = $this->postJson("/api/invitations/{$second->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);

        $reAccept->assertStatus(409)->assertJsonPath('error.code', 'CONFLICT');
    }

    public function test_preview_of_unknown_code_returns_410(): void
    {
        $response = $this->getJson('/api/invitations/NOEXISTE12');

        $response->assertStatus(410)->assertJsonPath('error.code', 'INVITATION_INVALID');
    }

    public function test_revoked_invitation_cannot_be_accepted(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $invitationId = $create->json('data.id');
        $code = $create->json('data.code');

        $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/invitations/{$invitationId}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ])->assertStatus(204);

        $accept = $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);
        $accept->assertStatus(410);
    }

    public function test_invitation_restricted_to_email_rejects_other_user(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $stranger = $this->registerUser();

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [
            'email' => 'invited-only@example.com',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $code = $create->json('data.code');

        $accept = $this->postJson("/api/invitations/{$code}/accept", [], ['Authorization' => "Bearer {$stranger['token']}"]);

        $accept->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_individual_workspace_cannot_create_invitations(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'individual']);

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_mine_lists_only_pending_invitations_for_my_email(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $invited = $this->registerUser(['email' => 'invited-mine@example.com']);
        $other = $this->registerUser();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [
            'email' => 'invited-mine@example.com',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $mine = $this->getJson('/api/invitations/mine', ['Authorization' => "Bearer {$invited['token']}"]);
        $mine->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.workspace_name', 'Workspace Test');

        $none = $this->getJson('/api/invitations/mine', ['Authorization' => "Bearer {$other['token']}"]);
        $none->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_inviting_an_existing_user_creates_an_in_app_notification(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $invited = $this->registerUser(['email' => 'invited-notify@example.com']);

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [
            'email' => 'invited-notify@example.com',
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);

        $this->getJson('/api/notifications', ['Authorization' => "Bearer {$invited['token']}"])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'workspace_invitation')
            ->assertJsonPath('data.0.route', '/')
            ->assertJsonPath('data.0.read_at', null);
    }

    public function test_only_owner_can_create_invitations(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $member = $this->registerUser();

        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$ctx['token']}"]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], ['Authorization' => "Bearer {$member['token']}"]);

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], ['Authorization' => "Bearer {$member['token']}"]);

        $response->assertStatus(403);
    }
}
