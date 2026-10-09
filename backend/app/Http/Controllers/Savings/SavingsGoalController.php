<?php

namespace App\Http\Controllers\Savings;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Savings\ContributeSavingsGoalRequest;
use App\Http\Requests\Savings\CreateSavingsGoalRequest;
use App\Http\Requests\Savings\TransferToGoalRequest;
use App\Http\Requests\Savings\UpdateSavingsGoalRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Savings\SavingsGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Savings Goals', description: 'Metas de ahorro de un workspace y sus aportes/retiros')]
class SavingsGoalController extends Controller
{
    public function __construct(private readonly SavingsGoalService $goals)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/savings/goals',
        tags: ['Savings Goals'],
        summary: 'Listar metas de ahorro del workspace',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filtro de estado de la meta (valor libre, se pasa tal cual al stored procedure)',
                schema: new OA\Schema(type: 'string', enum: ['active', 'completed', 'cancelled'], default: 'active'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de metas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/SavingsGoal'),
                    ),
                ]),
            ),
        ],
    )]
    public function index(Request $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $status = $request->query('status', 'active');

        return ApiResponse::ok(array_map(fn ($dto) => $dto->toArray(), $this->goals->list($membership, $status)));
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/savings/goals',
        tags: ['Savings Goals'],
        summary: 'Crear una meta de ahorro',
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
                required: ['name', 'target_amount'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150, example: 'Viaje a Bariloche'),
                    new OA\Property(
                        property: 'target_amount',
                        type: 'number',
                        format: 'float',
                        minimum: 0.01,
                        example: 100000,
                    ),
                    new OA\Property(
                        property: 'due_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        description: 'Debe ser posterior a hoy',
                        example: '2026-12-31',
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Meta creada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SavingsGoal', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function store(CreateSavingsGoalRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $goal = $this->goals->create($membership, $request->validated(), $request->user()->id);

        return ApiResponse::created($goal->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/savings/goals/{goalId}',
        tags: ['Savings Goals'],
        summary: 'Actualizar una meta de ahorro',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'goalId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'target_amount'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150),
                    new OA\Property(property: 'target_amount', type: 'number', format: 'float', minimum: 0.01),
                    new OA\Property(
                        property: 'due_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        description: 'Debe ser posterior a hoy',
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Meta actualizada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SavingsGoal', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 404,
                description: 'Meta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(UpdateSavingsGoalRequest $request, int $workspaceId, int $goalId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $goal = $this->goals->update($membership, $goalId, $request->validated(), $request->user()->id);

        return ApiResponse::ok($goal->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/savings/goals/{goalId}',
        tags: ['Savings Goals'],
        summary: 'Cancelar una meta de ahorro',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'goalId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Meta cancelada'),
            new OA\Response(
                response: 404,
                description: 'Meta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $goalId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $this->goals->cancel($membership, $goalId, $request->user()->id);

        return ApiResponse::noContent();
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/savings/goals/{goalId}/contribute',
        tags: ['Savings Goals'],
        summary: 'Registrar un aporte o retiro sobre una meta de ahorro',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'goalId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'amount'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['contribution', 'withdrawal']),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 5000),
                    new OA\Property(property: 'note', type: 'string', nullable: true, maxLength: 255),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Meta actualizada tras el aporte/retiro',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SavingsGoal', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 404,
                description: 'Meta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos o saldo insuficiente para retirar',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function contribute(ContributeSavingsGoalRequest $request, int $workspaceId, int $goalId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $data = $request->validated();

        $dto = $this->goals->contribute(
            $membership,
            $goalId,
            $data['type'],
            (float) $data['amount'],
            $data['note'] ?? null,
            $request->user()->id,
        );

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/savings/goals/{goalId}/movements',
        tags: ['Savings Goals'],
        summary: 'Listar los movimientos (aportes/retiros) de una meta de ahorro',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'goalId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de movimientos de la meta',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/SavingsGoalMovement'),
                    ),
                ]),
            ),
            new OA\Response(
                response: 404,
                description: 'Meta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function movements(Request $request, int $workspaceId, int $goalId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        return ApiResponse::ok(array_map(fn ($dto) => $dto->toArray(), $this->goals->movements($membership, $goalId)));
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/savings/goals/{goalId}/transfer-from-wallet',
        tags: ['Savings Goals'],
        summary: 'Transferir fondos desde la billetera de ahorro hacia una meta',
        parameters: [
            new OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'goalId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 5000),
                    new OA\Property(property: 'note', type: 'string', nullable: true, maxLength: 255),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transferencia realizada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        properties: [
                            new OA\Property(
                                property: 'wallet_balance',
                                type: 'number',
                                format: 'float',
                                example: 10000,
                            ),
                            new OA\Property(property: 'goal', ref: '#/components/schemas/SavingsGoal', type: 'object'),
                        ],
                        type: 'object',
                    ),
                ]),
            ),
            new OA\Response(
                response: 404,
                description: 'Meta inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos o saldo insuficiente en la billetera',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function transfer(TransferToGoalRequest $request, int $workspaceId, int $goalId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $data = $request->validated();

        $result = $this->goals->transferFromWallet(
            $membership,
            $goalId,
            (float) $data['amount'],
            $data['note'] ?? null,
            $request->user()->id,
        );

        return ApiResponse::ok([
            'wallet_balance' => $result['wallet_balance'],
            'goal' => $result['goal']->toArray(),
        ]);
    }
}
