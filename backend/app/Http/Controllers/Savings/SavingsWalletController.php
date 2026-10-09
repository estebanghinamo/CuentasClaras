<?php

namespace App\Http\Controllers\Savings;

use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Savings\CreateSavingsMovementRequest;
use App\Http\Requests\Savings\ListSavingsMovementsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Savings\SavingsWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Savings Wallet', description: 'Billetera de ahorro del workspace y sus movimientos')]
class SavingsWalletController extends Controller
{
    public function __construct(private readonly SavingsWalletService $savings)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/savings/wallet',
        tags: ['Savings Wallet'],
        summary: 'Obtener la billetera de ahorro del workspace',
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
                description: 'Billetera de ahorro',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SavingsWallet', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function wallet(Request $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        return ApiResponse::ok($this->savings->get($membership)->toArray());
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/savings/movements',
        tags: ['Savings Wallet'],
        summary: 'Listar movimientos de la billetera de ahorro (paginado)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20),
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['deposit', 'withdraw']),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pagina de movimientos',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/SavingsMovement'),
                    ),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Parametros de paginacion invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function movements(ListSavingsMovementsRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $page = (int) ($request->validated('page') ?? 1);
        $perPage = (int) ($request->validated('per_page') ?? 20);
        $type = $request->validated('type');

        $result = $this->savings->listMovements($membership, $page, $perPage, $type);

        return ApiResponse::paginated(
            array_map(fn ($dto) => $dto->toArray(), $result['items']),
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'last_page' => max(1, (int) ceil($result['total'] / $perPage)),
            ],
        );
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/savings/movements',
        tags: ['Savings Wallet'],
        summary: 'Registrar un deposito o retiro en la billetera de ahorro',
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
                required: ['type', 'amount'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['deposit', 'withdraw']),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 5000),
                    new OA\Property(property: 'note', type: 'string', nullable: true, maxLength: 255),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Movimiento registrado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/SavingsMovement', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos o saldo insuficiente para retirar',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function storeMovement(CreateSavingsMovementRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $data = $request->validated();

        $dto = $data['type'] === 'deposit'
            ? $this->savings->deposit(
                $membership,
                (float) $data['amount'],
                $data['note'] ?? null,
                $request->user()->id,
            )
            : $this->savings->withdraw(
                $membership,
                (float) $data['amount'],
                $data['note'] ?? null,
                $request->user()->id,
            );

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/savings/wallet/history',
        tags: ['Savings Wallet'],
        summary: 'Obtener el historial mensual de saldo de la billetera de ahorro',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'months',
                in: 'query',
                required: false,
                description: 'Cantidad de meses hacia atras',
                schema: new OA\Schema(type: 'integer', default: 12),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Serie historica de saldos por mes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'year', type: 'integer', example: 2026),
                                new OA\Property(property: 'month', type: 'integer', example: 9),
                                new OA\Property(
                                    property: 'balance_end',
                                    type: 'number',
                                    format: 'float',
                                    example: 15000.50,
                                ),
                            ],
                            type: 'object',
                        ),
                    ),
                ]),
            ),
        ],
    )]
    public function history(Request $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $months = (int) $request->query('months', 12);

        return ApiResponse::ok($this->savings->history($membership, $months));
    }
}
