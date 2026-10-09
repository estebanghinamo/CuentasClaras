<?php

namespace Tests\Feature\Notifications;

use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'audit_logs', 'notifications', 'notification_preferences', 'savings_wallet', 'workspace_invitations',
            'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    /** Genera una notificacion real via el unico productor actual: invitar a un usuario existente. */
    private function createNotificationFor(array $invitedUser): void
    {
        $ctx = $this->createWorkspaceAsOwner();
        $this->postJson("/api/workspaces/{$ctx['workspace_id']}/invitations", [
            'email' => $invitedUser['email'],
        ], ['Authorization' => "Bearer {$ctx['token']}"])->assertStatus(201);
    }

    public function test_unread_count_and_mark_read(): void
    {
        $user = $this->registerUser(['email' => 'notif-user@example.com']);
        $this->createNotificationFor($user);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 1);

        $notificationId = $this->getJson('/api/notifications', $auth)->json('data.0.id');

        $this->postJson("/api/notifications/{$notificationId}/read", [], $auth)
            ->assertStatus(200)->assertJsonPath('data.read_at', fn ($value) => $value !== null);

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 0);

        // Sigue apareciendo en el listado (registro permanente), solo que ya leida.
        $this->getJson('/api/notifications', $auth)
            ->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_mark_all_read(): void
    {
        $user = $this->registerUser(['email' => 'notif-all@example.com']);
        $this->createNotificationFor($user);
        $this->createNotificationFor($user);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 2);

        $this->postJson('/api/notifications/read-all', [], $auth)
            ->assertStatus(200)->assertJsonPath('data.updated_count', 2);

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 0);
    }

    public function test_list_supports_pagination_and_unread_only_filter(): void
    {
        $user = $this->registerUser(['email' => 'notif-paginate@example.com']);
        $this->createNotificationFor($user);
        $this->createNotificationFor($user);
        $this->createNotificationFor($user);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $firstPage = $this->getJson('/api/notifications?page=1&per_page=2', $auth);
        $firstPage->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 2);

        $secondPage = $this->getJson('/api/notifications?page=2&per_page=2', $auth);
        $secondPage->assertStatus(200)->assertJsonCount(1, 'data');

        $firstId = $firstPage->json('data.0.id');
        $this->postJson("/api/notifications/{$firstId}/read", [], $auth)->assertStatus(200);

        $unreadOnly = $this->getJson('/api/notifications?unread_only=1', $auth);
        $unreadOnly->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_cannot_mark_read_a_notification_of_another_user(): void
    {
        $owner = $this->registerUser(['email' => 'notif-owner@example.com']);
        $this->createNotificationFor($owner);
        $notificationId = $this->getJson('/api/notifications', ['Authorization' => "Bearer {$owner['token']}"])->json('data.0.id');

        $stranger = $this->registerUser();

        $this->postJson("/api/notifications/{$notificationId}/read", [], ['Authorization' => "Bearer {$stranger['token']}"])
            ->assertStatus(404);
    }

    public function test_preferences_return_defaults_when_never_set(): void
    {
        $user = $this->registerUser(['email' => 'notif-prefs-default@example.com']);

        $this->getJson('/api/notifications/preferences', ['Authorization' => "Bearer {$user['token']}"])
            ->assertStatus(200)
            ->assertJsonPath('data.service_reminder_days', [3, 1])
            ->assertJsonPath('data.budget_alert_levels', ['warning', 'reached', 'exceeded'])
            ->assertJsonPath('data.channels.in_app', true)
            ->assertJsonPath('data.channels.push', true)
            ->assertJsonPath('data.channels.email', false)
            ->assertJsonPath('data.muted_types', []);
    }

    public function test_preferences_can_be_updated_and_persist(): void
    {
        $user = $this->registerUser(['email' => 'notif-prefs-update@example.com']);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $payload = [
            'service_reminder_days' => [7],
            'budget_alert_levels' => ['exceeded'],
            'channels' => ['in_app' => true, 'push' => false, 'email' => true],
            'muted_types' => ['smart_suggestion'],
        ];

        $this->putJson('/api/notifications/preferences', $payload, $auth)
            ->assertStatus(200)
            ->assertJsonPath('data.service_reminder_days', [7])
            ->assertJsonPath('data.channels.push', false)
            ->assertJsonPath('data.muted_types', ['smart_suggestion']);

        $this->getJson('/api/notifications/preferences', $auth)
            ->assertStatus(200)
            ->assertJsonPath('data.service_reminder_days', [7])
            ->assertJsonPath('data.budget_alert_levels', ['exceeded']);
    }

    public function test_preferences_reject_invalid_muted_type(): void
    {
        $user = $this->registerUser(['email' => 'notif-prefs-invalid@example.com']);

        $this->putJson('/api/notifications/preferences', [
            'service_reminder_days' => [3],
            'budget_alert_levels' => ['warning'],
            'channels' => ['in_app' => true, 'push' => true, 'email' => false],
            'muted_types' => ['not_a_real_type'],
        ], ['Authorization' => "Bearer {$user['token']}"])->assertStatus(422);
    }

    /** Array vacío es un valor válido para estos 3 campos ("nada silenciado" / "ningún día de recordatorio") - 'required' en Laravel rechaza arrays vacíos, por eso el FormRequest usa 'present'. */
    public function test_preferences_accept_empty_arrays(): void
    {
        $user = $this->registerUser(['email' => 'notif-prefs-empty-arrays@example.com']);

        $this->putJson('/api/notifications/preferences', [
            'service_reminder_days' => [],
            'budget_alert_levels' => [],
            'channels' => ['in_app' => true, 'push' => false, 'email' => false],
            'muted_types' => [],
        ], ['Authorization' => "Bearer {$user['token']}"])
            ->assertStatus(200)
            ->assertJsonPath('data.service_reminder_days', [])
            ->assertJsonPath('data.budget_alert_levels', [])
            ->assertJsonPath('data.muted_types', []);
    }
}
