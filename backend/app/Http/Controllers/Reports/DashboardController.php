<?php

namespace App\Http\Controllers\Reports;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\DashboardHistoryRequest;
use App\Http\Requests\Reports\DashboardRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Reports\DashboardService;
use App\Support\Period;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard', description: 'Resumen financiero mensual de un workspace')]
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/dashboard',
        tags: ['Dashboard'],
        summary: 'Obtener el resumen financiero del mes (por defecto, el mes actual)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 2000, maximum: 2100),
            ),
            new OA\Parameter(
                name: 'month',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumen del mes solicitado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Dashboard', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'year/month invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(DashboardRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $filters = $request->validated();

        $period = isset($filters['year'], $filters['month'])
            ? Period::fromYearMonth((int) $filters['year'], (int) $filters['month'])
            : Period::current();

        $dto = $this->dashboard->get($membership, $period);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/dashboard/history',
        tags: ['Dashboard'],
        summary: 'Obtener el historico mensual de ingresos/gastos del workspace',
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
                description: 'Serie historica mensual',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            required: [
                                'year', 'month', 'income', 'expenses', 'services', 'installments',
                                'available', 'is_closed',
                            ],
                            properties: [
                                new OA\Property(property: 'year', type: 'integer'),
                                new OA\Property(property: 'month', type: 'integer'),
                                new OA\Property(property: 'income', type: 'number', format: 'float'),
                                new OA\Property(property: 'expenses', type: 'number', format: 'float'),
                                new OA\Property(property: 'services', type: 'number', format: 'float'),
                                new OA\Property(property: 'installments', type: 'number', format: 'float'),
                                new OA\Property(property: 'available', type: 'number', format: 'float'),
                                new OA\Property(
                                    property: 'is_closed',
                                    type: 'boolean',
                                    description: 'true si el mes ya tiene un cierre mensual registrado',
                                ),
                            ],
                            type: 'object',
                        ),
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
    public function history(DashboardHistoryRequest $request, int $workspaceId): JsonResponse
    {
        $months = (int) ($request->validated()['months'] ?? 12);

        return ApiResponse::ok($this->dashboard->history($workspaceId, $months));
    }
}
