<?php

namespace Tests\Feature\Notifications;

use App\Services\Notifications\NotificationDispatcher;
use Tests\Support\CreatesWorkspace;
use Tests\Support\ResetsTables;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use CreatesWorkspace, ResetsTables;

    protected function tearDown(): void
    {
        $this->truncate([
            'notifications', 'notification_preferences', 'audit_logs', 'savings_wallet',
            'workspace_invitations', 'workspace_users', 'workspaces', 'personal_access_tokens', 'users',
        ]);
        parent::tearDown();
    }

    public function test_muted_type_receives_no_in_app_notification(): void
    {
        $user = $this->registerUser(['email' => 'dispatcher-muted@example.com']);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $this->putJson('/api/notifications/preferences', [
            'service_reminder_days' => [3],
            'budget_alert_levels' => ['warning', 'reached', 'exceeded'],
            'channels' => ['in_app' => true, 'push' => false, 'email' => false],
            'muted_types' => ['smart_suggestion'],
        ], $auth)->assertStatus(200);

        app(NotificationDispatcher::class)->notifyUser(
            $user['user_id'], 'smart_suggestion', 'Título', 'Cuerpo', null, null, [],
        );

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 0);
    }

    public function test_non_muted_type_creates_in_app_notification(): void
    {
        $user = $this->registerUser(['email' => 'dispatcher-unmuted@example.com']);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        app(NotificationDispatcher::class)->notifyUser(
            $user['user_id'], 'smart_suggestion', 'Título', 'Cuerpo', null, null, [],
        );

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 1);
    }

    public function test_budget_alert_skipped_when_level_not_in_preferences(): void
    {
        $user = $this->registerUser(['email' => 'dispatcher-budget-skip@example.com']);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $this->putJson('/api/notifications/preferences', [
            'service_reminder_days' => [3],
            'budget_alert_levels' => ['exceeded'],
            'channels' => ['in_app' => true, 'push' => false, 'email' => false],
            'muted_types' => [],
        ], $auth)->assertStatus(200);

        app(NotificationDispatcher::class)->notifyUser(
            $user['user_id'], 'budget_alert', 'Presupuesto', 'Cuerpo', null, null, ['level' => 'warning'],
        );

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 0);
    }

    public function test_budget_alert_delivered_when_level_matches_preferences(): void
    {
        $user = $this->registerUser(['email' => 'dispatcher-budget-match@example.com']);
        $auth = ['Authorization' => "Bearer {$user['token']}"];

        $this->putJson('/api/notifications/preferences', [
            'service_reminder_days' => [3],
            'budget_alert_levels' => ['exceeded'],
            'channels' => ['in_app' => true, 'push' => false, 'email' => false],
            'muted_types' => [],
        ], $auth)->assertStatus(200);

        app(NotificationDispatcher::class)->notifyUser(
            $user['user_id'], 'budget_alert', 'Presupuesto', 'Cuerpo', null, null, ['level' => 'exceeded'],
        );

        $this->getJson('/api/notifications/unread-count', $auth)
            ->assertStatus(200)->assertJsonPath('data.count', 1);
    }

    public function test_notify_users_reaches_every_user_in_the_list(): void
    {
        $first = $this->registerUser(['email' => 'dispatcher-bulk-1@example.com']);
        $second = $this->registerUser(['email' => 'dispatcher-bulk-2@example.com']);

        app(NotificationDispatcher::class)->notifyUsers(
            [$first['user_id'], $second['user_id']], 'month_closed', 'Cierre', 'Cuerpo', null, null, [],
        );

        $this->getJson('/api/notifications/unread-count', ['Authorization' => "Bearer {$first['token']}"])
            ->assertStatus(200)->assertJsonPath('data.count', 1);
        $this->getJson('/api/notifications/unread-count', ['Authorization' => "Bearer {$second['token']}"])
            ->assertStatus(200)->assertJsonPath('data.count', 1);
    }
}
