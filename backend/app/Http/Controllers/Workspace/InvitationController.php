<?php

namespace App\Http\Controllers\Workspace;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\CreateInvitationRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Workspace\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Invitations', description: 'Invitaciones a espacios: creacion, listado, preview publico y aceptacion')]
class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitations)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/invitations',
        tags: ['Invitations'],
        summary: 'Listar las invitaciones del espacio (requiere ser owner)',
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
                description: 'Lista de invitaciones',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Invitation'),
                    ),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos owner de este espacio',
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

        $items = array_map(fn ($dto) => $dto->toArray(), $this->invitations->list($membership));

        return ApiResponse::ok($items);
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/invitations',
        tags: ['Invitations'],
        summary: 'Crear una invitacion al espacio (requiere ser owner)',
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
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        nullable: true,
                        maxLength: 150,
                        description: 'Si se envia, solo ese email puede aceptar la invitacion',
                    ),
                    new OA\Property(
                        property: 'expires_in_days',
                        type: 'integer',
                        nullable: true,
                        minimum: 1,
                        maximum: 30,
                        example: 7,
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Invitacion creada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Invitation', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos owner de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 429,
                description: 'Demasiadas invitaciones creadas, intenta mas tarde',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function store(CreateInvitationRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->invitations->create($membership, $request->validated());

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/invitations/{invitationId}',
        tags: ['Invitations'],
        summary: 'Revocar una invitacion (requiere ser owner)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'invitationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Invitacion revocada'),
            new OA\Response(
                response: 403,
                description: 'No sos owner de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Invitacion inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $invitationId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->invitations->revoke($membership, $invitationId);

        return ApiResponse::noContent();
    }

    #[OA\Get(
        path: '/invitations/{code}',
        tags: ['Invitations'],
        summary: 'Ver el preview publico de una invitacion (sin autenticacion)',
        security: [],
        parameters: [new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preview de la invitacion',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/InvitationPreview', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 404,
                description: 'Invitacion inexistente, expirada o revocada',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 429,
                description: 'Demasiados intentos, intenta mas tarde',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function preview(string $code): JsonResponse
    {
        $dto = $this->invitations->preview($code);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Post(
        path: '/invitations/{code}/accept',
        tags: ['Invitations'],
        summary: 'Aceptar una invitacion (requiere autenticacion)',
        parameters: [new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invitacion aceptada, el usuario ahora es miembro del espacio',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Workspace', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 403,
                description: 'La invitacion esta restringida a otro email',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Invitacion inexistente, expirada o revocada',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function accept(Request $request, string $code): JsonResponse
    {
        $user = $request->user();
        $dto = $this->invitations->accept($code, $user->id, $user->email);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Get(
        path: '/invitations/mine',
        tags: ['Invitations'],
        summary: 'Listar las invitaciones pendientes para el email del usuario autenticado',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de invitaciones pendientes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Invitation'),
                    ),
                ]),
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function mine(Request $request): JsonResponse
    {
        $items = array_map(
            fn ($dto) => $dto->toArray(),
            $this->invitations->listPendingForEmail($request->user()->email),
        );

        return ApiResponse::ok($items);
    }
}
