<?php

namespace App\Jobs;

use App\Services\Closing\MonthlyClosingService;
use App\Support\Period;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Cierre mensual de un workspace (ESPECIFICACION_TECNICA.md M-13). Despachado
 * por CloseMonthCommand (cron día 1, o reproceso manual).
 */
class CloseMonthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $workspaceId,
        public readonly int $year,
        public readonly int $month,
    ) {
        $this->onQueue('closings');
    }

    public function handle(MonthlyClosingService $closings): void
    {
        $closings->close($this->workspaceId, Period::fromYearMonth($this->year, $this->month));
    }
}
