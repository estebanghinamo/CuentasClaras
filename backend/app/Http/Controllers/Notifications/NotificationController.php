<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotificationListRequest;
use App\Http\Requests\Notifications\UpdateNotificationPreferencesRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Notifications',
    description: 'Notificaciones del usuario y sus preferencias (no dependen de un workspace)',
)]
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    #[OA\Get(
        path: '/notifications',
        tags: ['Notifications'],
        summary: 'Listar las notificaciones del usuario autenticado (paginado)',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 20),
            ),
            new OA\Parameter(
                name: 'unread_only',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pagina de notificaciones',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Notification'),
                    ),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Parametros de paginacion invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(NotificationListRequest $request): JsonResponse
    {
        $data = $request->validated();
        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 20);
        $unreadOnly = (bool) ($data['unread_only'] ?? false);

        $result = $this->notifications->list($request->user()->id, $page, $perPage, $unreadOnly);

        return ApiResponse::paginated(
            array_map(fn ($dto) => $dto->toArray(), $result['items']),
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'last_page' => max(1, (int) ceil($result['total'] / $perPage)),
            ],
        );
    }

    #[OA\Get(
        path: '/notifications/unread-count',
        tags: ['Notifications'],
        summary: 'Obtener la cantidad de notificaciones no leidas',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cantidad de no leidas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        required: ['count'],
                        properties: [new OA\Property(property: 'count', type: 'integer', example: 3)],
                        type: 'object',
                    ),
                ]),
            ),
        ],
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::ok(['count' => $this->notifications->unreadCount($request->user()->id)]);
    }

    #[OA\Post(
        path: '/notifications/{notificationId}/read',
        tags: ['Notifications'],
        summary: 'Marcar una notificacion como leida',
        parameters: [
            new OA\Parameter(
                name: 'notificationId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificacion actualizada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Notification', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 404,
                description: 'Notificacion inexistente o de otro usuario',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function markRead(Request $request, int $notificationId): JsonResponse
    {
        $dto = $this->notifications->markRead($request->user()->id, $notificationId);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Post(
        path: '/notifications/read-all',
        tags: ['Notifications'],
        summary: 'Marcar todas las notificaciones del usuario como leidas',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cantidad de notificaciones actualizadas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        required: ['updated_count'],
                        properties: [new OA\Property(property: 'updated_count', type: 'integer', example: 5)],
                        type: 'object',
                    ),
                ]),
            ),
        ],
    )]
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllRead($request->user()->id);

        return ApiResponse::ok(['updated_count' => $count]);
    }

    #[OA\Get(
        path: '/notifications/preferences',
        tags: ['Notifications'],
        summary: 'Obtener las preferencias de notificacion del usuario',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preferencias actuales',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/NotificationPreferences',
                        type: 'object',
                    ),
                ]),
            ),
        ],
    )]
    public function getPreferences(Request $request): JsonResponse
    {
        return ApiResponse::ok($this->notifications->getPreferences($request->user()->id)->toArray());
    }

    #[OA\Put(
        path: '/notifications/preferences',
        tags: ['Notifications'],
        summary: 'Actualizar las preferencias de notificacion del usuario',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['service_reminder_days', 'budget_alert_levels', 'channels', 'muted_types'],
                properties: [
                    new OA\Property(
                        property: 'service_reminder_days',
                        type: 'array',
                        items: new OA\Items(type: 'integer', minimum: 0, maximum: 30),
                        maxItems: 5,
                        example: [3, 1],
                    ),
                    new OA\Property(
                        property: 'budget_alert_levels',
                        type: 'array',
                        items: new OA\Items(type: 'string', enum: ['warning', 'reached', 'exceeded']),
                    ),
                    new OA\Property(
                        property: 'channels',
                        required: ['in_app', 'push', 'email'],
                        properties: [
                            new OA\Property(property: 'in_app', type: 'boolean'),
                            new OA\Property(property: 'push', type: 'boolean'),
                            new OA\Property(property: 'email', type: 'boolean'),
                        ],
                        type: 'object',
                    ),
                    new OA\Property(
                        property: 'muted_types',
                        type: 'array',
                        items: new OA\Items(
                            type: 'string',
                            enum: [
                                'service_due_soon', 'service_overdue', 'installment_due_soon', 'budget_alert',
                                'workspace_invitation', 'workspace_member_joined', 'month_closed',
                                'savings_goal_completed', 'smart_suggestion',
                            ],
                        ),
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preferencias actualizadas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/NotificationPreferences',
                        type: 'object',
                    ),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $data = $request->validated();
        $dto = $this->notifications->updatePreferences(
            $request->user()->id,
            $data['service_reminder_days'],
            $data['budget_alert_levels'],
            $data['channels'],
            $data['muted_types'],
        );

        return ApiResponse::ok($dto->toArray());
    }
}
