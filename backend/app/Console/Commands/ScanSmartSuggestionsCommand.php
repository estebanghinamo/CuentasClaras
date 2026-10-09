<?php

namespace App\Console\Commands;

use App\Jobs\ScanSmartSuggestionsJob;
use App\Repositories\WorkspaceRepository;
use Illuminate\Console\Command;

/**
 * Escanea gastos recurrentes de todos los workspaces (ESPECIFICACION_TECNICA.md
 * M-18 §4). Scheduler: lunes 03:00. También se dispara por workspace puntual
 * desde CloseMonthJob tras cada cierre (M-13).
 */
class ScanSmartSuggestionsCommand extends Command
{
    protected $signature = 'suggestions:scan {--workspace=}';

    protected $description = 'Escanea gastos recurrentes para sugerir servicios nuevos.';

    public function handle(WorkspaceRepository $workspaces): int
    {
        $workspaceOption = $this->option('workspace');

        $workspaceIds = $workspaceOption !== null
            ? [(int) $workspaceOption]
            : $workspaces->listAllIds();

        foreach ($workspaceIds as $workspaceId) {
            ScanSmartSuggestionsJob::dispatch($workspaceId);
        }

        $this->info(sprintf('%d job(s) de escaneo despachados.', count($workspaceIds)));

        return self::SUCCESS;
    }
}
