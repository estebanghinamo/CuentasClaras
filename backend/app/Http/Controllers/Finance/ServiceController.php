<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateServiceRequest;
use App\Http\Requests\Finance\ListServicesRequest;
use App\Http\Requests\Finance\UpdateServiceRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Services', description: 'Servicios recurrentes de un espacio (luz, gas, alquiler, etc.)')]
class ServiceController extends Controller
{
    public function __construct(private readonly ServiceService $services)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/services',
        tags: ['Services'],
        summary: 'Listar los servicios de un espacio',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'active',
                in: 'query',
                required: false,
                description: 'Filtro de estado. Por defecto "true" (solo activos).',
                schema: new OA\Schema(type: 'string', enum: ['all', 'true', 'false'], default: 'true'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de servicios',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Service'),
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
    public function index(ListServicesRequest $request, int $workspaceId): JsonResponse
    {
        $active = $request->validated('active') ?? 'true';
        $items = array_map(fn ($dto) => $dto->toArray(), $this->services->list($workspaceId, $active));

        return ApiResponse::ok($items);
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/services',
        tags: ['Services'],
        summary: 'Crear un servicio nuevo',
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
                required: ['name', 'amount', 'is_estimated', 'due_day_start', 'late_fee_type', 'late_fee_value'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Luz'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 15000.5),
                    new OA\Property(property: 'is_estimated', type: 'boolean'),
                    new OA\Property(property: 'due_day_start', type: 'integer', minimum: 1, maximum: 31, example: 10),
                    new OA\Property(
                        property: 'due_day_end',
                        type: 'integer',
                        minimum: 1,
                        maximum: 31,
                        nullable: true,
                        description: 'Debe ser mayor o igual a due_day_start',
                    ),
                    new OA\Property(property: 'late_fee_type', type: 'string', enum: ['percentage', 'fixed']),
                    new OA\Property(
                        property: 'late_fee_value',
                        type: 'number',
                        format: 'float',
                        minimum: 0,
                        description: 'Si late_fee_type es percentage, no puede superar 100',
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Servicio creado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Service', type: 'object'),
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
    public function store(CreateServiceRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->services->create($membership, $request->validated());

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/services/{serviceId}',
        tags: ['Services'],
        summary: 'Actualizar un servicio',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'serviceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'name', 'amount', 'is_estimated', 'due_day_start',
                    'late_fee_type', 'late_fee_value', 'active',
                ],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150),
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'is_estimated', type: 'boolean'),
                    new OA\Property(property: 'due_day_start', type: 'integer', minimum: 1, maximum: 31),
                    new OA\Property(
                        property: 'due_day_end',
                        type: 'integer',
                        minimum: 1,
                        maximum: 31,
                        nullable: true,
                        description: 'Debe ser mayor o igual a due_day_start',
                    ),
                    new OA\Property(property: 'late_fee_type', type: 'string', enum: ['percentage', 'fixed']),
                    new OA\Property(
                        property: 'late_fee_value',
                        type: 'number',
                        format: 'float',
                        minimum: 0,
                        description: 'Si late_fee_type es percentage, no puede superar 100',
                    ),
                    new OA\Property(property: 'active', type: 'boolean'),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Servicio actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Service', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Servicio inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(UpdateServiceRequest $request, int $workspaceId, int $serviceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->services->update($membership, $serviceId, $request->validated());

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/services/{serviceId}',
        tags: ['Services'],
        summary: 'Eliminar un servicio',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'serviceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Servicio eliminado'),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Servicio inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $serviceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->services->delete($membership, $serviceId);

        return ApiResponse::noContent();
    }
}
