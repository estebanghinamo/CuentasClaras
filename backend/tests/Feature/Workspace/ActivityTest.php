<?php

namespace Tests\Feature\Workspace;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class ActivityTest extends TestCase
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

    public function test_creating_and_updating_workspace_generates_activity_entries(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $this->putJson("/api/workspaces/{$ctx['workspace_id']}", [
            'name' => 'Nuevo nombre', 'currency' => 'ARS', 'type' => 'shared_joint',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/activity", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.action', 'updated')
            ->assertJsonPath('data.0.new_value.name', 'Nuevo nombre')
            ->assertJsonPath('data.1.action', 'created')
            ->assertJsonPath('data.1.old_value', null)
            ->assertJsonPath('data.0.user_name', 'Owner Test');
    }

    public function test_activity_paginates(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        foreach (range(1, 3) as $i) {
            $this->putJson("/api/workspaces/{$ctx['workspace_id']}", [
                'name' => "Nombre {$i}", 'currency' => 'ARS', 'type' => 'shared_joint',
            ], ['Authorization' => "Bearer {$ctx['token']}"]);
        }

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/activity?per_page=2", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_activity_filters_by_entity_type(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/activity?entity_type=workspace_invitation", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.entity_type', 'workspace_invitation');
    }

    public function test_non_member_cannot_see_activity(): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $stranger = $this->registerUser();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/activity", [
            'Authorization' => "Bearer {$stranger['token']}",
        ]);

        $response->assertStatus(404);
    }

    public function test_per_page_over_100_is_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/activity?per_page=101", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
