<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateSettlementPaymentRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Settlement\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Settlement', description: 'Liquidacion de gastos compartidos entre miembros de un workspace')]
class SettlementController extends Controller
{
    public function __construct(private readonly SettlementService $settlements)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/settlement',
        tags: ['Settlement'],
        summary: 'Obtener el resumen de liquidacion (balances, transferencias sugeridas y pagos ya registrados)',
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
                description: 'Resumen de liquidacion',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SettlementSummary', type: 'object'),
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
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        return ApiResponse::ok($this->settlements->getSummary($membership)->toArray());
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/settlement-payments',
        tags: ['Settlement'],
        summary: 'Registrar un pago que salda (total o parcialmente) una deuda entre dos miembros',
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
                required: ['from_user_id', 'to_user_id', 'amount'],
                properties: [
                    new OA\Property(property: 'from_user_id', type: 'integer', minimum: 1),
                    new OA\Property(
                        property: 'to_user_id',
                        type: 'integer',
                        minimum: 1,
                        description: 'Debe ser distinto de from_user_id',
                    ),
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'note', type: 'string', maxLength: 255, nullable: true),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pago registrado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SettlementPayment', type: 'object'),
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
    public function storePayment(CreateSettlementPaymentRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->settlements->registerPayment($membership, $request->user()->id, $request->validated());

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/settlement-payments/{settlementPaymentId}',
        tags: ['Settlement'],
        summary: 'Eliminar un pago de liquidacion registrado',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(
                name: 'settlementPaymentId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Pago eliminado'),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Pago inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroyPayment(Request $request, int $workspaceId, int $settlementPaymentId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->settlements->deletePayment($membership, $request->user()->id, $settlementPaymentId);

        return ApiResponse::noContent();
    }
}
