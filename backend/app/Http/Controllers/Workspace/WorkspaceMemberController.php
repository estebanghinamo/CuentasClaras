<?php

namespace App\Http\Controllers\Workspace;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Workspace\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Workspace Members', description: 'Miembros de un espacio: listado, salida y remocion')]
class WorkspaceMemberController extends Controller
{
    public function __construct(private readonly WorkspaceService $workspaces)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/members',
        tags: ['Workspace Members'],
        summary: 'Listar los miembros de un espacio (requiere ser miembro)',
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
                description: 'Lista de miembros',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/WorkspaceMember'),
                    ),
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
        $items = array_map(fn ($dto) => $dto->toArray(), $this->workspaces->listMembers($workspaceId));

        return ApiResponse::ok($items);
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/members/{userId}',
        tags: ['Workspace Members'],
        summary: 'Remover un miembro del espacio (requiere ser owner)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'userId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Miembro removido'),
            new OA\Response(
                response: 403,
                description: 'No sos owner de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Espacio o miembro inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $userId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->workspaces->removeMember($membership, $userId);

        return ApiResponse::noContent();
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/leave',
        tags: ['Workspace Members'],
        summary: 'Abandonar el espacio (requiere ser miembro)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Saliste del espacio'),
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
    public function leave(Request $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->workspaces->removeMember($membership, $membership->userId);

        return ApiResponse::noContent();
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/onboarding/complete',
        tags: ['Workspace Members'],
        summary: 'Marcar el onboarding del espacio como completado para el usuario actual',
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
                description: 'Onboarding marcado como completado',
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
    public function completeOnboarding(Request $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->workspaces->markOnboarding($membership);

        return ApiResponse::ok($dto->toArray());
    }
}
