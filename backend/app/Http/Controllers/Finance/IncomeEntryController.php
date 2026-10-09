<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateIncomeEntryRequest;
use App\Http\Requests\Finance\ListIncomeEntriesRequest;
use App\Http\Requests\Finance\UpdateIncomeEntryRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\IncomeEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Income', description: 'Ingresos mensuales del espacio (workspace)')]
class IncomeEntryController extends Controller
{
    public function __construct(private readonly IncomeEntryService $incomeEntries)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/income-entries',
        tags: ['Income'],
        summary: 'Listar los ingresos de un mes (con totales por usuario)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 2000, maximum: 2100),
            ),
            new OA\Parameter(
                name: 'month',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumen de ingresos del mes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/IncomeSummary', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Parametros year/month invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(ListIncomeEntriesRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $filters = $request->validated();

        $summary = $this->incomeEntries->list($membership, (int) $filters['year'], (int) $filters['month']);

        return ApiResponse::ok($summary->toArray());
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/income-entries',
        tags: ['Income'],
        summary: 'Registrar un ingreso',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'concept', 'date'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150000.5),
                    new OA\Property(property: 'concept', type: 'string', maxLength: 150, example: 'Sueldo'),
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-09-01'),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Ingreso creado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/IncomeEntry', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function store(CreateIncomeEntryRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->incomeEntries->create($membership, $request->user()->id, $request->validated());

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/income-entries/{incomeEntryId}',
        tags: ['Income'],
        summary: 'Actualizar un ingreso (requiere ser el autor o owner del espacio)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'incomeEntryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'concept', 'date'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150000.5),
                    new OA\Property(property: 'concept', type: 'string', maxLength: 150, example: 'Sueldo'),
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-09-01'),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ingreso actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/IncomeEntry', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio o no podes editar este ingreso',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Ingreso inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(UpdateIncomeEntryRequest $request, int $workspaceId, int $incomeEntryId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->incomeEntries->update($membership, $incomeEntryId, $request->validated());

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/income-entries/{incomeEntryId}',
        tags: ['Income'],
        summary: 'Eliminar un ingreso (requiere ser el autor o owner del espacio)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'incomeEntryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Ingreso eliminado'),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio o no podes eliminar este ingreso',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Ingreso inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $incomeEntryId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->incomeEntries->delete($membership, $incomeEntryId);

        return ApiResponse::noContent();
    }
}
