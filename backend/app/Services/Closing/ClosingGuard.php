<?php

namespace App\Services\Closing;

use App\Exceptions\MonthClosedException;
use App\Repositories\MonthlyClosingRepository;
use App\Support\Period;
use Illuminate\Validation\ValidationException;

/**
 * Guard de período (ver ESPECIFICACION_TECNICA.md §0.10). Se invoca desde los
 * Services de cada módulo con carga de datos, ANTES de llamar al Repository.
 */
class ClosingGuard
{
    public function __construct(private readonly MonthlyClosingRepository $closings)
    {
    }

    public function assertPeriodOpen(int $workspaceId, Period $period): void
    {
        if ($this->closings->exists($workspaceId, $period)) {
            throw new MonthClosedException("El mes {$period->label()} ya está cerrado y no admite nuevas cargas.");
        }
    }

    public function isOpen(int $workspaceId, Period $period): bool
    {
        return !$this->closings->exists($workspaceId, $period);
    }

    public function assertPeriodNotTooFar(Period $period): void
    {
        if ($period->isAfter(Period::current()->next())) {
            throw ValidationException::withMessages([
                'period' => ['No se admiten cargas más allá del mes siguiente.'],
            ]);
        }
    }
}
