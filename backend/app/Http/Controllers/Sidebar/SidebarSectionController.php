<?php

namespace App\Http\Controllers\Sidebar;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sidebar\SetSidebarSectionsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Sidebar\SidebarSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Sidebar Sections', description: 'Secciones visibles del sidebar por espacio (preferencia del miembro)')]
class SidebarSectionController extends Controller
{
    public function __construct(private readonly SidebarSectionService $sections)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/sidebar-sections',
        tags: ['Sidebar Sections'],
        summary: 'Obtener las secciones del sidebar habilitadas para el espacio (requiere ser miembro)',
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
                description: 'Secciones habilitadas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SidebarSections', type: 'object'),
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
    public function index(Request $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        return ApiResponse::ok($this->sections->list($membership)->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/sidebar-sections',
        tags: ['Sidebar Sections'],
        summary: 'Actualizar las secciones del sidebar habilitadas para el espacio (requiere ser miembro)',
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
                required: ['sections'],
                properties: [
                    new OA\Property(
                        property: 'sections',
                        type: 'array',
                        items: new OA\Items(
                            type: 'string',
                            enum: ['budgets', 'savings', 'goals', 'members', 'activity'],
                        ),
                        example: ['budgets', 'savings'],
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Secciones actualizadas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SidebarSections', type: 'object'),
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
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(SetSidebarSectionsRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->sections->set($membership, $request->validated()['sections']);

        return ApiResponse::ok($dto->toArray());
    }
}
