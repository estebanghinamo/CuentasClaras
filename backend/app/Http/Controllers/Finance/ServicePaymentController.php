<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ListServicePaymentsRequest;
use App\Http\Requests\Finance\PayServicePaymentRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\ServicePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'ServicePayments', description: 'Cuotas mensuales generadas por cada servicio recurrente y su pago')]
class ServicePaymentController extends Controller
{
    public function __construct(private readonly ServicePaymentService $servicePayments)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/service-payments',
        tags: ['ServicePayments'],
        summary: 'Listar los pagos de servicios de un periodo (mes/anio)',
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
                description: 'Pagos del periodo con totales agregados',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'items',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/ServicePayment'),
                            ),
                            new OA\Property(
                                property: 'total_expected',
                                type: 'number',
                                format: 'float',
                                example: 45000,
                            ),
                            new OA\Property(
                                property: 'total_paid',
                                type: 'number',
                                format: 'float',
                                example: 30000,
                            ),
                            new OA\Property(property: 'pending_count', type: 'integer', example: 1),
                            new OA\Property(property: 'overdue_count', type: 'integer', example: 0),
                        ],
                    ),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Parametros invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(ListServicePaymentsRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $data = $request->validated();

        $result = $this->servicePayments->listForPeriod($membership, (int) $data['year'], (int) $data['month']);

        return ApiResponse::ok([
            'items' => array_map(fn ($dto) => $dto->toArray(), $result['items']),
            'total_expected' => $result['total_expected'],
            'total_paid' => $result['total_paid'],
            'pending_count' => $result['pending_count'],
            'overdue_count' => $result['overdue_count'],
        ]);
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/service-payments/{servicePaymentId}/pay',
        tags: ['ServicePayments'],
        summary: 'Registrar el pago de una cuota de servicio',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'servicePaymentId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'amount_paid',
                        type: 'number',
                        format: 'float',
                        nullable: true,
                        description: 'Monto realmente pagado. Si se omite, se usa el monto esperado del servicio.',
                    ),
                    new OA\Property(
                        property: 'paid_at',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        description: 'Fecha de pago (Y-m-d), no puede ser futura. Si se omite, se usa hoy.',
                    ),
                    new OA\Property(
                        property: 'apply_late_fee',
                        type: 'boolean',
                        nullable: true,
                        description: 'Si se debe aplicar el recargo por mora calculado automaticamente',
                    ),
                    new OA\Property(
                        property: 'fee_difference',
                        type: 'number',
                        format: 'float',
                        nullable: true,
                        description: 'Ajuste manual sobre el recargo calculado. Puede ser negativo (ej. si el '
                            . 'recargo realmente cobrado fue menor al calculado).',
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuota marcada como pagada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ServicePayment', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cuota de servicio inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function pay(PayServicePaymentRequest $request, int $workspaceId, int $servicePaymentId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->servicePayments->pay($membership, $servicePaymentId, $request->validated(), $request->user()->id);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/service-payments/{servicePaymentId}/unpay',
        tags: ['ServicePayments'],
        summary: 'Revertir el pago de una cuota de servicio',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'servicePaymentId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuota marcada como pendiente nuevamente',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ServicePayment', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Cuota de servicio inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function unpay(Request $request, int $workspaceId, int $servicePaymentId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->servicePayments->unpay($membership, $servicePaymentId, $request->user()->id);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/service-payments/overdue',
        tags: ['ServicePayments'],
        summary: 'Listar las cuotas de servicio vencidas (y marcarlas como overdue)',
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
                description: 'Lista de cuotas vencidas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/ServicePayment'),
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
    public function overdue(Request $request, int $workspaceId): JsonResponse
    {
        $items = array_map(fn ($dto) => $dto->toArray(), $this->servicePayments->listOverdue($workspaceId));

        return ApiResponse::ok($items);
    }
}
