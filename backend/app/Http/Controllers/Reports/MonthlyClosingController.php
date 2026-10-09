<?php

namespace App\Http\Controllers\Reports;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\AllocateClosingRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Closing\MonthlyClosingService;
use App\Support\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'MonthlyClosings', description: 'Cierres mensuales del workspace y asignacion del sobrante')]
class MonthlyClosingController extends Controller
{
    public function __construct(private readonly MonthlyClosingService $closings)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/closings',
        tags: ['MonthlyClosings'],
        summary: 'Listar los cierres mensuales del workspace',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de cierres',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/MonthlyClosing'),
                    ),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(Request $request, int $workspaceId): JsonResponse
    {
        $items = array_map(fn ($dto) => $dto->toArray(), $this->closings->list($workspaceId));

        return ApiResponse::ok($items);
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/closings/{year}/{month}',
        tags: ['MonthlyClosings'],
        summary: 'Obtener el cierre (o el calculo en vivo si aun no fue cerrado) de un mes',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'year',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'month',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cierre del mes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/MonthlyClosing', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Periodo invalido o inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function show(Request $request, int $workspaceId, int $year, int $month): JsonResponse
    {
        $dto = $this->closings->get($workspaceId, Period::fromYearMonth($year, $month));

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/closings/{year}/{month}/allocate',
        tags: ['MonthlyClosings'],
        summary: 'Asignar el sobrante del cierre (monedero, metas de ahorro y/o mes siguiente)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'year',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'month',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['to_wallet', 'to_goals', 'to_next_month'],
                properties: [
                    new OA\Property(property: 'to_wallet', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(
                        property: 'to_goals',
                        type: 'array',
                        maxItems: 20,
                        items: new OA\Items(
                            required: ['goal_id', 'amount'],
                            properties: [
                                new OA\Property(property: 'goal_id', type: 'integer'),
                                new OA\Property(property: 'amount', type: 'number', format: 'float'),
                            ],
                            type: 'object',
                        ),
                    ),
                    new OA\Property(property: 'to_next_month', type: 'number', format: 'float', minimum: 0),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sobrante asignado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/MonthlyClosing', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos (montos negativos, suma en cero, etc.)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function allocate(AllocateClosingRequest $request, int $workspaceId, int $year, int $month): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->closings->allocate($membership, Period::fromYearMonth($year, $month), $request->validated());

        return ApiResponse::ok($dto->toArray());
    }
}
