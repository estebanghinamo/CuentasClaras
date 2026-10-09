<?php

namespace App\Http\Controllers\Reports;

use App\DTOs\Finance\ExpenseFiltersDto;
use App\DTOs\Workspace\WorkspaceMembershipDto;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Repositories\WorkspaceExportRepository;
use App\Services\Closing\MonthlyClosingService;
use App\Services\Export\XlsxExportService;
use App\Services\Finance\ExpenseService;
use App\Services\Finance\InstallmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceExportController extends Controller
{
    /**
     * Tope de páginas al loopear sp_expense_list para traer "todos" los
     * gastos (el SP solo pagina, no tiene un modo "sin límite" - ver
     * CalidadYSeguridad.md/M-16). Con perPage=100 (el máximo que acepta
     * ListExpensesRequest) esto cubre hasta 5000 gastos; si algún workspace
     * supera eso, corta ahí en vez de colgar el request.
     */
    private const MAX_EXPORT_PAGES = 50;

    public function __construct(
        private readonly ExpenseService $expenses,
        private readonly InstallmentService $installments,
        private readonly MonthlyClosingService $closings,
        private readonly WorkspaceExportRepository $exportData,
        private readonly XlsxExportService $xlsx,
    ) {
    }

    public function export(Request $request, int $workspaceId): Response
    {
        /** @var WorkspaceMembershipDto $membership */
        $membership = $request->attributes->get('membership');

        $content = $this->xlsx->build([
            [
                'sheetName' => 'Gastos',
                'columns' => [
                    'date' => 'Fecha',
                    'user_name' => 'Cargado por',
                    'category_name' => 'Categoría',
                    'description' => 'Descripción',
                    'payment_method' => 'Medio de pago',
                    'amount' => 'Monto',
                    'paid_by_user_name' => 'Pagado por',
                ],
                'rows' => $this->allExpenses($membership),
            ],
            [
                'sheetName' => 'Ingresos',
                'columns' => [
                    'date' => 'Fecha',
                    'user_name' => 'Cargado por',
                    'concept' => 'Concepto',
                    'amount' => 'Monto',
                ],
                'rows' => $this->exportData->incomeEntries($workspaceId)['entries'],
            ],
            [
                'sheetName' => 'Servicios',
                'columns' => [
                    'service_name' => 'Servicio',
                    'year' => 'Año',
                    'month' => 'Mes',
                    'due_date_end' => 'Vence',
                    'expected_amount' => 'Monto esperado',
                    'status' => 'Estado',
                    'amount_paid' => 'Monto pagado',
                    'late_fee_applied' => 'Recargo',
                    'paid_at' => 'Fecha de pago',
                    'paid_by_name' => 'Pagado por',
                ],
                'rows' => $this->exportData->servicePayments($workspaceId)['items'],
            ],
            [
                'sheetName' => 'Cuotas',
                'columns' => [
                    'description' => 'Descripción',
                    'category_name' => 'Categoría',
                    'total_amount' => 'Monto total',
                    'installments_count' => 'Cantidad de cuotas',
                    'installment_amount' => 'Monto por cuota',
                    'start_date' => 'Fecha de inicio',
                    'status' => 'Estado',
                    'paid_count' => 'Cuotas pagadas',
                    'remaining_count' => 'Cuotas restantes',
                    'remaining_amount' => 'Monto restante',
                ],
                'rows' => array_map(
                    fn ($dto) => $dto->toArray(),
                    $this->installments->list($membership, 'all'),
                ),
            ],
            [
                'sheetName' => 'Cierres',
                'columns' => [
                    'year' => 'Año',
                    'month' => 'Mes',
                    'total_income' => 'Ingresos',
                    'total_expenses' => 'Gastos',
                    'total_services' => 'Servicios',
                    'total_installments' => 'Cuotas',
                    'remaining_amount' => 'Disponible',
                    'savings_generated' => 'Ahorro generado',
                    'allocation_status' => 'Estado de asignación',
                    'closed_at' => 'Fecha de cierre',
                ],
                'rows' => array_map(fn ($dto) => $dto->toArray(), $this->closings->list($workspaceId)),
            ],
        ]);

        return ApiResponse::xlsx($content, 'cuentas-claras.xlsx');
    }

    /** @return array<int, array<string, mixed>> */
    private function allExpenses(WorkspaceMembershipDto $membership): array
    {
        $items = [];
        $page = 1;
        do {
            $filters = new ExpenseFiltersDto(page: $page, perPage: 100);
            $result = $this->expenses->list($membership, $filters);
            $items = [...$items, ...array_map(fn ($dto) => $dto->toArray(), $result['items'])];
            $page++;
        } while (count($items) < $result['total'] && $page <= self::MAX_EXPORT_PAGES);

        return $items;
    }
}
