<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CopyBudgetsRequest;
use App\Http\Requests\Finance\ListBudgetsRequest;
use App\Http\Requests\Finance\UpsertBudgetRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\BudgetService;
use App\Support\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Budgets', description: 'Presupuestos mensuales por categoria de un workspace')]
class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgets)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/budgets',
        tags: ['Budgets'],
        summary: 'Obtener el resumen de presupuestos de un periodo (mes/anio actual por defecto)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
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
                description: 'Resumen de presupuestos del periodo',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/BudgetSummary', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Parametros de periodo invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(ListBudgetsRequest $request, int $workspaceId): JsonResponse
    {
        $filters = $request->validated();
        $period = isset($filters['year'], $filters['month'])
            ? Period::fromYearMonth((int) $filters['year'], (int) $filters['month'])
            : Period::current();

        return ApiResponse::ok($this->budgets->summary($workspaceId, $period->year, $period->month)->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/budgets',
        tags: ['Budgets'],
        summary: 'Crear o actualizar el presupuesto de una categoria para un periodo',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_id', 'year', 'month', 'limit_amount'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'integer', example: 3),
                    new OA\Property(
                        property: 'year',
                        type: 'integer',
                        minimum: 2000,
                        maximum: 2100,
                        example: 2026,
                    ),
                    new OA\Property(property: 'month', type: 'integer', minimum: 1, maximum: 12, example: 9),
                    new OA\Property(
                        property: 'limit_amount',
                        type: 'number',
                        format: 'float',
                        minimum: 0.01,
                        example: 50000,
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Presupuesto existente actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Budget', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 201,
                description: 'Presupuesto creado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Budget', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function upsert(UpsertBudgetRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $result = $this->budgets->upsert($membership, $request->user()->id, $request->validated());

        return $result['created']
            ? ApiResponse::created($result['dto']->toArray())
            : ApiResponse::ok($result['dto']->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/budgets/{budgetId}',
        tags: ['Budgets'],
        summary: 'Eliminar un presupuesto',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'budgetId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Presupuesto eliminado'),
            new OA\Response(
                response: 404,
                description: 'Presupuesto inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $budgetId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $this->budgets->delete($membership, $request->user()->id, $budgetId);

        return ApiResponse::noContent();
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/budgets/copy',
        tags: ['Budgets'],
        summary: 'Copiar presupuestos de un periodo a otro',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['from_year', 'from_month', 'to_year', 'to_month', 'overwrite'],
                properties: [
                    new OA\Property(
                        property: 'from_year',
                        type: 'integer',
                        minimum: 2000,
                        maximum: 2100,
                        example: 2026,
                    ),
                    new OA\Property(property: 'from_month', type: 'integer', minimum: 1, maximum: 12, example: 8),
                    new OA\Property(
                        property: 'to_year',
                        type: 'integer',
                        minimum: 2000,
                        maximum: 2100,
                        example: 2026,
                    ),
                    new OA\Property(property: 'to_month', type: 'integer', minimum: 1, maximum: 12, example: 9),
                    new OA\Property(
                        property: 'overwrite',
                        type: 'boolean',
                        description: 'Sobrescribir presupuestos existentes en el periodo destino',
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cantidad de presupuestos copiados',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'copied_count', type: 'integer', example: 5),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos (ej. periodo destino igual al de origen)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function copy(CopyBudgetsRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $count = $this->budgets->copyFromPeriod($membership, $request->validated());

        return ApiResponse::ok(['copied_count' => $count]);
    }
}
