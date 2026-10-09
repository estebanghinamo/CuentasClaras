<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateCategoryRequest;
use App\Http\Requests\Finance\UpdateCategoryRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Categories', description: 'Categorias de gastos de un workspace')]
class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/categories',
        tags: ['Categories'],
        summary: 'Listar categorias del workspace (por defecto y personalizadas)',
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
                description: 'Lista de categorias',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Category'),
                    ),
                ]),
            ),
        ],
    )]
    public function index(Request $request, int $workspaceId): JsonResponse
    {
        $items = array_map(fn ($dto) => $dto->toArray(), $this->categories->list($workspaceId));

        return ApiResponse::ok($items);
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/categories',
        tags: ['Categories'],
        summary: 'Crear una categoria personalizada',
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
                required: ['name', 'icon', 'color'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        minLength: 1,
                        maxLength: 100,
                        example: 'Mascotas',
                    ),
                    new OA\Property(
                        property: 'icon',
                        type: 'string',
                        maxLength: 50,
                        pattern: '^[a-z0-9_]+$',
                        example: 'pets',
                    ),
                    new OA\Property(
                        property: 'color',
                        type: 'string',
                        pattern: '^#[0-9A-Fa-f]{6}$',
                        example: '#FF5733',
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Categoria creada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Category', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function store(CreateCategoryRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->categories->create($membership, $request->validated());

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/categories/{categoryId}',
        tags: ['Categories'],
        summary: 'Actualizar una categoria',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'icon', 'color'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', minLength: 1, maxLength: 100),
                    new OA\Property(property: 'icon', type: 'string', maxLength: 50, pattern: '^[a-z0-9_]+$'),
                    new OA\Property(property: 'color', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoria actualizada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Category', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 404,
                description: 'Categoria inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(UpdateCategoryRequest $request, int $workspaceId, int $categoryId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->categories->update($membership, $categoryId, $request->validated());

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/categories/{categoryId}',
        tags: ['Categories'],
        summary: 'Eliminar una categoria personalizada',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Categoria eliminada'),
            new OA\Response(
                response: 404,
                description: 'Categoria inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'No se puede eliminar (categoria por defecto o con gastos asociados)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $categoryId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->categories->delete($membership, $categoryId);

        return ApiResponse::noContent();
    }
}
