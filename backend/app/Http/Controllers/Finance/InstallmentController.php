<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateInstallmentRequest;
use App\Http\Requests\Finance\ListInstallmentsRequest;
use App\Http\Requests\Finance\PayInstallmentPaymentRequest;
use App\Http\Requests\Finance\UpdateInstallmentRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\InstallmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Installments', description: 'Compras en cuotas y sus pagos mensuales')]
class InstallmentController extends Controller
{
    public function __construct(private readonly InstallmentService $installments)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/installments',
        tags: ['Installments'],
        summary: 'Listar los planes de cuotas de un espacio',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filtro de estado. Por defecto "active".',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['active', 'completed', 'cancelled', 'all'],
                    default: 'active',
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de planes de cuotas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Installment'),
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
    public function index(ListInstallmentsRequest $request): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $status = $request->validated('status') ?? 'active';

        return ApiResponse::ok(array_map(
            fn ($dto) => $dto->toArray(),
            $this->installments->list($membership, $status),
        ));
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/installments/{installmentId}',
        tags: ['Installments'],
        summary: 'Obtener un plan de cuotas con el detalle de sus pagos',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'installmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plan de cuotas encontrado (incluye payments)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Installment', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Plan de cuotas inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function show(Request $request, int $workspaceId, int $installmentId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        return ApiResponse::ok($this->installments->get($membership, $installmentId)->toArray());
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/installments',
        tags: ['Installments'],
        summary: 'Crear un plan de cuotas nuevo',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['description', 'total_amount', 'installments_count', 'start_date'],
                properties: [
                    new OA\Property(property: 'description', type: 'string', maxLength: 255, example: 'Heladera nueva'),
                    new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 60000),
                    new OA\Property(
                        property: 'installments_count',
                        type: 'integer',
                        minimum: 1,
                        maximum: 120,
                        example: 12,
                    ),
                    new OA\Property(
                        property: 'start_date',
                        type: 'string',
                        format: 'date',
                        description: 'Fecha de la primera cuota (Y-m-d)',
                    ),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Plan de cuotas creado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Installment', type: 'object'),
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
    public function store(CreateInstallmentRequest $request): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        return ApiResponse::created($this->installments->create($membership, $request->validated())->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/installments/{installmentId}',
        tags: ['Installments'],
        summary: 'Actualizar un plan de cuotas (solo descripcion y categoria)',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'installmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['description'],
                properties: [
                    new OA\Property(property: 'description', type: 'string', maxLength: 255),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plan de cuotas actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Installment', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Plan de cuotas inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(UpdateInstallmentRequest $request, int $workspaceId, int $installmentId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $installment = $this->installments->update($membership, $installmentId, $request->validated());

        return ApiResponse::ok($installment->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/installments/{installmentId}',
        tags: ['Installments'],
        summary: 'Eliminar un plan de cuotas',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'installmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Plan de cuotas eliminado'),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Plan de cuotas inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $installmentId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $this->installments->delete($membership, $installmentId);

        return ApiResponse::noContent();
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/installments/{installmentId}/payments/{paymentId}/pay',
        tags: ['Installments'],
        summary: 'Registrar el pago de una cuota especifica de un plan',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'installmentId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'paymentId',
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
                        property: 'paid_at',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        description: 'Fecha de pago (Y-m-d). Si se omite, se usa hoy.',
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
                    new OA\Property(property: 'data', ref: '#/components/schemas/InstallmentPayment', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Plan de cuotas o pago inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function pay(
        PayInstallmentPaymentRequest $request,
        int $workspaceId,
        int $installmentId,
        int $paymentId,
    ): JsonResponse {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $payment = $this->installments->pay(
            $membership,
            $installmentId,
            $paymentId,
            $request->validated('paid_at'),
            $request->user()->id,
        );

        return ApiResponse::ok($payment->toArray());
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/installments/{installmentId}/payments/{paymentId}/unpay',
        tags: ['Installments'],
        summary: 'Revertir el pago de una cuota especifica de un plan',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'installmentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'paymentId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuota marcada como pendiente nuevamente',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/InstallmentPayment', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Plan de cuotas o pago inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function unpay(Request $request, int $workspaceId, int $installmentId, int $paymentId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $payment = $this->installments->unpay($membership, $installmentId, $paymentId, $request->user()->id);

        return ApiResponse::ok($payment->toArray());
    }
}
