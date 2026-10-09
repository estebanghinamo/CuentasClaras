<?php

namespace App\Jobs;

use App\Services\Finance\SmartSuggestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Escaneo de gastos recurrentes de un workspace (ESPECIFICACION_TECNICA.md
 * M-18). Disparado por M-13 tras cada cierre, y por suggestions:scan semanal.
 */
class ScanSmartSuggestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('default');
    }

    public function handle(SmartSuggestionService $suggestions): void
    {
        $suggestions->scan($this->workspaceId);
    }
}
