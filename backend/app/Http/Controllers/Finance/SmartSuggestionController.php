<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateServiceRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\SmartSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'SmartSuggestions',
    description: 'Sugerencias automaticas de servicios recurrentes detectados en gastos',
)]
class SmartSuggestionController extends Controller
{
    public function __construct(private readonly SmartSuggestionService $suggestions)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/suggestions',
        tags: ['SmartSuggestions'],
        summary: 'Listar sugerencias del workspace filtradas por estado',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['pending', 'accepted', 'dismissed'], default: 'pending'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de sugerencias',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/SmartSuggestion'),
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
        $status = $request->query('status', 'pending');
        $items = array_map(fn ($dto) => $dto->toArray(), $this->suggestions->list($workspaceId, $status));

        return ApiResponse::ok($items);
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/suggestions/{suggestionId}/accept',
        tags: ['SmartSuggestions'],
        summary: 'Aceptar una sugerencia: crea un servicio recurrente con los datos indicados',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'suggestionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'amount', 'is_estimated', 'due_day_start', 'late_fee_type', 'late_fee_value'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Netflix'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'is_estimated', type: 'boolean'),
                    new OA\Property(property: 'due_day_start', type: 'integer', minimum: 1, maximum: 31),
                    new OA\Property(property: 'due_day_end', type: 'integer', minimum: 1, maximum: 31, nullable: true),
                    new OA\Property(property: 'late_fee_type', type: 'string', enum: ['percentage', 'fixed']),
                    new OA\Property(property: 'late_fee_value', type: 'number', format: 'float'),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Sugerencia aceptada y servicio creado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        required: ['suggestion', 'service'],
                        properties: [
                            new OA\Property(
                                property: 'suggestion',
                                ref: '#/components/schemas/SmartSuggestion',
                                type: 'object',
                            ),
                            new OA\Property(property: 'service', ref: '#/components/schemas/Service', type: 'object'),
                        ],
                        type: 'object',
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
                description: 'Sugerencia inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function accept(CreateServiceRequest $request, int $workspaceId, int $suggestionId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $result = $this->suggestions->accept($membership, $suggestionId, $request->validated());

        return ApiResponse::created([
            'suggestion' => $result['suggestion']->toArray(),
            'service' => $result['service']->toArray(),
        ]);
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/suggestions/{suggestionId}/dismiss',
        tags: ['SmartSuggestions'],
        summary: 'Descartar una sugerencia',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'suggestionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sugerencia descartada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SmartSuggestion', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Sugerencia inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function dismiss(Request $request, int $workspaceId, int $suggestionId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->suggestions->dismiss($membership, $suggestionId);

        return ApiResponse::ok($dto->toArray());
    }
}
