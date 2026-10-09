<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\HealthScoreHistoryRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Reports\HealthScoreService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'HealthScores', description: 'Historico del puntaje de salud financiera de un workspace')]
class HealthScoreController extends Controller
{
    public function __construct(private readonly HealthScoreService $scores)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/health-scores',
        tags: ['HealthScores'],
        summary: 'Obtener el historico de puntajes de salud financiera del workspace',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(
                name: 'months',
                in: 'query',
                required: false,
                description: 'Cantidad de meses hacia atras a incluir (por defecto 12)',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 36, default: 12),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de puntajes, uno por mes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/HealthScore'),
                    ),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'months invalido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(HealthScoreHistoryRequest $request, int $workspaceId): JsonResponse
    {
        $months = (int) ($request->validated()['months'] ?? 12);

        $items = array_map(fn ($dto) => $dto->toArray(), $this->scores->list($workspaceId, $months));

        return ApiResponse::ok($items);
    }
}
