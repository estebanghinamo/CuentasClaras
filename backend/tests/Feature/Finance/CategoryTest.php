<?php

namespace Tests\Feature\Finance;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'expenses', 'categories', 'savings_wallet', 'workspace_invitations',
            'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    public function test_new_workspace_has_no_categories(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->getJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);

        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_create_update_and_delete_category(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $create = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'name' => 'Viajes', 'icon' => 'flight', 'color' => '#00FF00',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $create->assertStatus(201)->assertJsonPath('data.name', 'Viajes')->assertJsonPath('data.is_default', false);
        $categoryId = $create->json('data.id');

        $update = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/categories/{$categoryId}", [
            'name' => 'Viajes y turismo', 'icon' => 'flight_takeoff', 'color' => '#0000FF',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);
        $update->assertStatus(200)->assertJsonPath('data.name', 'Viajes y turismo');

        $delete = $this->deleteJson("/api/workspaces/{$ctx['workspace_id']}/categories/{$categoryId}", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $delete->assertStatus(204);

        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'category', 'entity_id' => $categoryId, 'action' => 'deleted']);
    }

    public function test_duplicate_category_name_returns_conflict(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'name' => 'Supermercado', 'icon' => 'shopping_cart', 'color' => '#4CAF50',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'name' => 'Supermercado', 'icon' => 'shopping_cart', 'color' => '#4CAF50',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'CONFLICT');
    }

    public function test_member_of_shared_separate_can_edit_categories(): void
    {
        $ctx = $this->createWorkspaceAsOwner(['type' => 'shared_separate']);
        $member = $this->registerUser();

        $invite = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [], [
            'Authorization' => "Bearer {$ctx['token']}",
        ]);
        $this->postJson("/api/invitations/{$invite->json('data.code')}/accept", [], [
            'Authorization' => "Bearer {$member['token']}",
        ]);

        $categoryId = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'name' => 'Supermercado', 'icon' => 'shopping_cart', 'color' => '#4CAF50',
        ], ['Authorization' => "Bearer {$ctx['token']}"])->json('data.id');

        $response = $this->putJson("/api/workspaces/{$ctx['workspace_id']}/categories/{$categoryId}", [
            'name' => 'Editado por member', 'icon' => 'edit', 'color' => '#123456',
        ], ['Authorization' => "Bearer {$member['token']}"]);

        $response->assertStatus(200)->assertJsonPath('data.name', 'Editado por member');
    }

    public function test_invalid_icon_and_color_are_rejected(): void
    {
        $ctx = $this->createWorkspaceAsOwner();

        $response = $this->postJson("/api/workspaces/{$ctx['workspace_id']}/categories", [
            'name' => 'Mala', 'icon' => 'Not Valid!', 'color' => 'red',
        ], ['Authorization' => "Bearer {$ctx['token']}"]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['details' => ['icon', 'color']]]);
    }
}
