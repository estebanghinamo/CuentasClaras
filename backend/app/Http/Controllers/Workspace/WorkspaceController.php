<?php

namespace App\Http\Controllers\Workspace;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\CreateWorkspaceRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Workspace\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Workspaces', description: 'Espacios de finanzas (individuales o compartidos) y su ciclo de vida')]
class WorkspaceController extends Controller
{
    public function __construct(private readonly WorkspaceService $workspaces)
    {
    }

    #[OA\Get(
        path: '/workspaces',
        tags: ['Workspaces'],
        summary: 'Listar los espacios del usuario autenticado',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de espacios',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Workspace'),
                    ),
                ]),
            ),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $items = array_map(fn ($dto) => $dto->toArray(), $this->workspaces->listForUser($request->user()->id));

        return ApiResponse::ok($items);
    }

    #[OA\Post(
        path: '/workspaces',
        tags: ['Workspaces'],
        summary: 'Crear un espacio nuevo',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'currency', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Finanzas del hogar'),
                    new OA\Property(
                        property: 'currency',
                        type: 'string',
                        example: 'ARS',
                        description: 'Codigo ISO de 3 letras, ver config/currencies.php',
                    ),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['individual', 'shared_joint', 'shared_separate', 'shared_settlement'],
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Espacio creado (el creador queda como owner)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Workspace', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function store(CreateWorkspaceRequest $request): JsonResponse
    {
        $dto = $this->workspaces->create($request->user()->id, $request->validated());

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}',
        tags: ['Workspaces'],
        summary: 'Obtener un espacio (requiere ser miembro)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Espacio encontrado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Workspace', type: 'object'),
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
        ],
    )]
    public function show(Request $request, int $workspaceId): JsonResponse
    {
        $dto = $this->workspaces->get($workspaceId, $request->user()->id);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}',
        tags: ['Workspaces'],
        summary: 'Actualizar un espacio (requiere ser owner)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'currency', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'currency', type: 'string', example: 'ARS'),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['individual', 'shared_joint', 'shared_separate', 'shared_settlement'],
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Espacio actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Workspace', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos owner de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(UpdateWorkspaceRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->workspaces->update($membership, $request->validated());

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}',
        tags: ['Workspaces'],
        summary: 'Eliminar un espacio (requiere ser owner)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Espacio eliminado'),
            new OA\Response(
                response: 403,
                description: 'No sos owner de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->workspaces->delete($membership);

        return ApiResponse::noContent();
    }
}
