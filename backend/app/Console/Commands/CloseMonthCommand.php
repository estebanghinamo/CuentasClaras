<?php

namespace App\Console\Commands;

use App\Jobs\CloseMonthJob;
use App\Repositories\WorkspaceRepository;
use App\Support\Period;
use Illuminate\Console\Command;

/**
 * Dispara el cierre del mes anterior (o el período/workspace indicado) - ver
 * ESPECIFICACION_TECNICA.md M-13 §4. El scheduler lo corre el día 1 a las
 * 00:30; también sirve para reprocesar manualmente un período/workspace puntual.
 */
class CloseMonthCommand extends Command
{
    protected $signature = 'closings:run {--year=} {--month=} {--workspace=}';

    protected $description = 'Cierra el mes anterior (o el período indicado) para uno o todos los workspaces.';

    public function handle(WorkspaceRepository $workspaces): int
    {
        $year = $this->option('year');
        $month = $this->option('month');
        $workspaceOption = $this->option('workspace');

        $period = ($year !== null && $month !== null)
            ? Period::fromYearMonth((int) $year, (int) $month)
            : Period::current()->previous();

        $current = Period::current();
        if ($period->equals($current) || $period->isAfter($current)) {
            $this->error('No se puede cerrar el mes en curso ni un mes futuro.');

            return self::FAILURE;
        }

        $workspaceIds = $workspaceOption !== null
            ? [(int) $workspaceOption]
            : $workspaces->listAllIds();

        foreach ($workspaceIds as $workspaceId) {
            CloseMonthJob::dispatch($workspaceId, $period->year, $period->month);
        }

        $this->info(sprintf('%d job(s) de cierre despachados para %s.', count($workspaceIds), $period->label()));

        return self::SUCCESS;
    }
}
