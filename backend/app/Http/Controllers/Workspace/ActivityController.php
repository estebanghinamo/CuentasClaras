<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\ActivityFiltersRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Activity', description: 'Historial de actividad (auditoria) de un espacio')]
class ActivityController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/activity',
        tags: ['Activity'],
        summary: 'Listar el historial de actividad del espacio (requiere ser miembro)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20),
            ),
            new OA\Parameter(
                name: 'entity_type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 50),
                example: 'expense',
            ),
            new OA\Parameter(
                name: 'user_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pagina de entradas de actividad',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/AuditLog'),
                    ),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Espacio inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Filtros invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(ActivityFiltersRequest $request, int $workspaceId): JsonResponse
    {
        $filters = $request->validated();
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 20);

        $result = $this->audit->list(
            $workspaceId,
            $page,
            $perPage,
            $filters['entity_type'] ?? null,
            isset($filters['user_id']) ? (int) $filters['user_id'] : null,
        );

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
}
