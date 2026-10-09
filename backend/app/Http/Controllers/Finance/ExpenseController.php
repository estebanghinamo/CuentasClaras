<?php

namespace App\Http\Controllers\Finance;

use App\DTOs\Finance\ExpenseFiltersDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateExpenseRequest;
use App\Http\Requests\Finance\ListExpensesRequest;
use App\Http\Requests\Finance\UpdateExpenseRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Finance\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Expenses', description: 'Gastos del espacio (workspace)')]
class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenses)
    {
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/expenses',
        tags: ['Expenses'],
        summary: 'Listar gastos (paginado, con filtros)',
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
                name: 'date_from',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
            ),
            new OA\Parameter(
                name: 'date_to',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                description: 'Debe ser >= date_from',
            ),
            new OA\Parameter(
                name: 'category_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
            new OA\Parameter(
                name: 'category_ids',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'array', maxItems: 20, items: new OA\Items(type: 'integer', minimum: 1)),
            ),
            new OA\Parameter(
                name: 'user_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
            new OA\Parameter(
                name: 'payment_method',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['cash', 'debit', 'credit', 'transfer', 'wallet', 'other']),
            ),
            new OA\Parameter(
                name: 'amount_min',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: 0),
            ),
            new OA\Parameter(
                name: 'amount_max',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: 0),
                description: 'Debe ser >= amount_min',
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 100),
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['date', 'amount', 'created_at'], default: 'date'),
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de gastos',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Expense'),
                    ),
                    new OA\Property(
                        property: 'meta',
                        description: 'Metadata de paginacion (distinta de PaginationMeta: usa page en vez '
                            . 'de current_page, y agrega total_amount)',
                        required: ['page', 'per_page', 'total', 'last_page', 'total_amount'],
                        properties: [
                            new OA\Property(property: 'page', type: 'integer', example: 1),
                            new OA\Property(property: 'per_page', type: 'integer', example: 20),
                            new OA\Property(property: 'total', type: 'integer', example: 57),
                            new OA\Property(property: 'last_page', type: 'integer', example: 3),
                            new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 15000),
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
                response: 422,
                description: 'Filtros invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function index(ListExpensesRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');
        $filters = ExpenseFiltersDto::fromRequest($request->validated());

        $result = $this->expenses->list($membership, $filters);

        return ApiResponse::paginated(
            array_map(fn ($dto) => $dto->toArray(), $result['items']),
            [
                'page' => $filters->page,
                'per_page' => $filters->perPage,
                'total' => $result['total'],
                'last_page' => max(1, (int) ceil($result['total'] / $filters->perPage)),
                'total_amount' => $result['total_amount'],
            ],
        );
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/expenses/{expenseId}',
        tags: ['Expenses'],
        summary: 'Obtener un gasto',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'expenseId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gasto encontrado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Expense', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Gasto inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function show(Request $request, int $workspaceId, int $expenseId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->expenses->get($membership, $expenseId);

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Post(
        path: '/workspaces/{workspaceId}/expenses',
        tags: ['Expenses'],
        summary: 'Registrar un gasto',
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
                required: ['amount', 'payment_method', 'date'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true, minimum: 1, example: 3),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        maxLength: 255,
                        example: 'Super',
                    ),
                    new OA\Property(
                        property: 'payment_method',
                        type: 'string',
                        enum: ['cash', 'debit', 'credit', 'transfer', 'wallet', 'other'],
                    ),
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-09-01'),
                    new OA\Property(
                        property: 'paid_by_user_id',
                        type: 'integer',
                        nullable: true,
                        minimum: 1,
                        example: 1,
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Gasto creado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Expense', type: 'object'),
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
    public function store(CreateExpenseRequest $request, int $workspaceId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->expenses->create($membership, $request->user()->id, $request->validated());

        return ApiResponse::created($dto->toArray());
    }

    #[OA\Put(
        path: '/workspaces/{workspaceId}/expenses/{expenseId}',
        tags: ['Expenses'],
        summary: 'Actualizar un gasto (requiere ser el autor o owner del espacio)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'expenseId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'payment_method', 'date'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 600),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true, minimum: 1, example: 3),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        maxLength: 255,
                        example: 'Super editado',
                    ),
                    new OA\Property(
                        property: 'payment_method',
                        type: 'string',
                        enum: ['cash', 'debit', 'credit', 'transfer', 'wallet', 'other'],
                    ),
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-09-01'),
                    new OA\Property(
                        property: 'paid_by_user_id',
                        type: 'integer',
                        nullable: true,
                        minimum: 1,
                        example: 1,
                    ),
                ],
            )),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gasto actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Expense', type: 'object'),
                ]),
            ),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio o no podes editar este gasto',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Gasto inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Datos invalidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function update(UpdateExpenseRequest $request, int $workspaceId, int $expenseId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $dto = $this->expenses->update($membership, $expenseId, $request->validated());

        return ApiResponse::ok($dto->toArray());
    }

    #[OA\Delete(
        path: '/workspaces/{workspaceId}/expenses/{expenseId}',
        tags: ['Expenses'],
        summary: 'Eliminar un gasto (requiere ser el autor o owner del espacio)',
        parameters: [
            new OA\Parameter(
                name: 'workspaceId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'expenseId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Gasto eliminado'),
            new OA\Response(
                response: 403,
                description: 'No sos miembro de este espacio o no podes eliminar este gasto',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
            new OA\Response(
                response: 404,
                description: 'Gasto inexistente',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError'),
            ),
        ],
    )]
    public function destroy(Request $request, int $workspaceId, int $expenseId): JsonResponse
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $this->expenses->delete($membership, $expenseId);

        return ApiResponse::noContent();
    }

    #[OA\Get(
        path: '/workspaces/{workspaceId}/expenses/payment-methods',
        tags: ['Expenses'],
        summary: 'Listar los metodos de pago usados en el espacio',
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
                description: 'Metodos de pago usados (valores distintos, sin orden garantizado)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            type: 'string',
                            enum: ['cash', 'debit', 'credit', 'transfer', 'wallet', 'other'],
                        ),
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
    public function paymentMethods(Request $request, int $workspaceId): JsonResponse
    {
        return ApiResponse::ok($this->expenses->paymentMethodsUsed($workspaceId));
    }
}
