<?php

namespace App\Services\Notifications;

use App\Mail\NotificationMail;
use App\Repositories\UserRepository;
use App\Services\Notifications\Channels\FcmChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Orquesta el envío multi-canal de una notificación a un conjunto de
 * usuarios, respetando sus preferencias (M-17 §4). Cada canal falla de forma
 * aislada (try/catch propio) - que falle FCM no debe impedir el mail, y
 * viceversa. El canal in-app (NotificationService::notify) es el único
 * síncrono real hacia la base; push/mail son best-effort.
 */
class NotificationDispatcher
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly PushDeviceService $pushDevices,
        private readonly FcmChannel $fcm,
        private readonly UserRepository $users,
    ) {}

    /**
     * @param int[] $userIds
     * @param array{level?:string} $payload usado para filtrar budget_alert contra budget_alert_levels
     */
    public function notifyUsers(
        array $userIds,
        string $type,
        string $title,
        string $body,
        ?string $route = null,
        ?int $workspaceId = null,
        array $payload = [],
    ): void {
        foreach ($userIds as $userId) {
            $this->notifyUser($userId, $type, $title, $body, $route, $workspaceId, $payload);
        }
    }

    public function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?string $route,
        ?int $workspaceId,
        array $payload,
    ): void {
        $preferences = $this->notifications->getPreferences($userId);

        if (in_array($type, $preferences->mutedTypes, true)) {
            return;
        }

        $isMutedBudgetLevel = $type === 'budget_alert'
            && isset($payload['level'])
            && !in_array($payload['level'], $preferences->budgetAlertLevels, true);

        if ($isMutedBudgetLevel) {
            return;
        }

        $isMutedReminderDay = $type === 'service_due_soon'
            && isset($payload['days'])
            && !in_array($payload['days'], $preferences->serviceReminderDays, true);

        if ($isMutedReminderDay) {
            return;
        }

        $channels = $preferences->channels;

        if ($channels['in_app'] ?? true) {
            $this->notifications->notify($userId, $type, $title, $body, $route, $workspaceId, $payload);
        }

        if ($channels['push'] ?? false) {
            $this->sendPush($userId, $title, $body, $type, $route, $workspaceId);
        }

        if ($channels['email'] ?? false) {
            $this->sendMail($userId, $title, $body, $route);
        }
    }

    private function sendPush(
        int $userId,
        string $title,
        string $body,
        string $type,
        ?string $route,
        ?int $workspaceId,
    ): void {
        try {
            $devices = $this->pushDevices->listByUsers([$userId]);

            foreach ($devices as $device) {
                $result = $this->fcm->send($device->token, $title, $body, [
                    'type' => $type,
                    'route' => $route ?? '',
                    'workspace_id' => $workspaceId !== null ? (string) $workspaceId : '',
                ]);

                if ($result['invalid_token']) {
                    $this->pushDevices->unregister($userId, $device->token);
                }
            }
        } catch (Throwable $e) {
            Log::error('NotificationDispatcher: fallo enviando push.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function sendMail(int $userId, string $title, string $body, ?string $route): void
    {
        try {
            $user = $this->users->findById($userId);
            if ($user === null || empty($user['email'])) {
                return;
            }

            $link = $route ? rtrim((string) config('cuentas.frontend_url'), '/').$route : null;
            // Sincronico a proposito (no ->queue()): igual que el push, para no
            // depender de un worker de colas corriendo - en dev nadie lo tiene
            // arriba por default, un mail encolado se queda esperando indefinido
            // (bug real reportado por el usuario, 2026-09-23).
            Mail::to($user['email'])->send(new NotificationMail($title, $body, $link));
        } catch (Throwable $e) {
            Log::error('NotificationDispatcher: fallo encolando mail.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
