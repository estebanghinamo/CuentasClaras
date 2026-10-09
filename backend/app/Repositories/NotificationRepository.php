<?php

namespace App\Repositories;

class NotificationRepository extends BaseRepository
{
    public function create(
        int $userId,
        ?int $workspaceId,
        string $type,
        string $title,
        string $body,
        ?string $route,
        array $payload = [],
    ): array {
        return $this->call('sp_notification_create', [
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'route' => $route,
            'payload' => $payload,
        ]);
    }

    /** @return array{total:int, items:array} */
    public function list(int $userId, int $page, int $perPage, bool $unreadOnly): array
    {
        return $this->call('sp_notification_list', [
            'user_id' => $userId,
            'page' => $page,
            'per_page' => $perPage,
            'unread_only' => $unreadOnly,
        ]);
    }

    public function unreadCount(int $userId): int
    {
        $result = $this->call('sp_notification_unread_count', ['user_id' => $userId]);

        return (int) $result['count'];
    }

    public function markRead(int $userId, int $notificationId): array
    {
        return $this->call('sp_notification_mark_read', ['user_id' => $userId, 'notification_id' => $notificationId]);
    }

    public function markAllRead(int $userId): int
    {
        $result = $this->call('sp_notification_mark_all_read', ['user_id' => $userId]);

        return (int) $result['updated_count'];
    }

    public function getPreferences(int $userId): array
    {
        return $this->call('sp_notification_preference_get', ['user_id' => $userId]);
    }

    public function upsertPreferences(
        int $userId,
        array $serviceReminderDays,
        array $budgetAlertLevels,
        array $channels,
        array $mutedTypes,
    ): array {
        return $this->call('sp_notification_preference_upsert', [
            'user_id' => $userId,
            'service_reminder_days' => $serviceReminderDays,
            'budget_alert_levels' => $budgetAlertLevels,
            'channels' => $channels,
            'muted_types' => $mutedTypes,
        ]);
    }
}
