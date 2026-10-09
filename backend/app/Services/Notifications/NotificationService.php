<?php

namespace App\Services\Notifications;

use App\DTOs\Notifications\NotificationDto;
use App\DTOs\Notifications\NotificationPreferencesDto;
use App\Exceptions\StoredProcedureException;
use App\Repositories\NotificationRepository;
use App\Services\Support\SpErrorMapper;

/**
 * Notificaciones puntuales creadas desde otros Services vía notify() (canal
 * in-app directo, ver NotificationDispatcher para el envío multi-canal según
 * preferencias), la bandeja del usuario (listar, contar sin leer, marcar), y
 * las preferencias de notificación por usuario (M-17).
 */
class NotificationService
{
    public function __construct(private readonly NotificationRepository $notifications)
    {
    }

    public function notify(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?string $route = null,
        ?int $workspaceId = null,
        array $payload = [],
    ): void {
        $this->notifications->create($userId, $workspaceId, $type, $title, $body, $route, $payload);
    }

    /** @return array{items: NotificationDto[], total: int} */
    public function list(int $userId, int $page, int $perPage, bool $unreadOnly): array
    {
        $result = $this->notifications->list($userId, $page, $perPage, $unreadOnly);

        return [
            'items' => array_map(fn (array $row) => NotificationDto::fromArray($row), $result['items']),
            'total' => (int) $result['total'],
        ];
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCount($userId);
    }

    public function markRead(int $userId, int $notificationId): NotificationDto
    {
        try {
            $row = $this->notifications->markRead($userId, $notificationId);
        } catch (StoredProcedureException $e) {
            throw SpErrorMapper::map($e);
        }

        return NotificationDto::fromArray($row);
    }

    public function markAllRead(int $userId): int
    {
        return $this->notifications->markAllRead($userId);
    }

    public function getPreferences(int $userId): NotificationPreferencesDto
    {
        return NotificationPreferencesDto::fromArray($this->notifications->getPreferences($userId));
    }

    public function updatePreferences(
        int $userId,
        array $serviceReminderDays,
        array $budgetAlertLevels,
        array $channels,
        array $mutedTypes,
    ): NotificationPreferencesDto {
        $row = $this->notifications->upsertPreferences(
            $userId,
            $serviceReminderDays,
            $budgetAlertLevels,
            $channels,
            $mutedTypes,
        );

        return NotificationPreferencesDto::fromArray($row);
    }
}
