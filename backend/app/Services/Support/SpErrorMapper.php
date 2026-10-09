<?php

namespace App\Services\Support;

use App\Exceptions\ConflictException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidStateException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StoredProcedureException;
use Illuminate\Validation\ValidationException;
use Throwable;

class SpErrorMapper
{
    /**
     * Traduce el código de error de un Stored Procedure a la excepción que debe
     * ver el cliente. $overrides permite pasar una excepción específica por
     * spCode cuando el mensaje genérico no alcanza (ver ESPECIFICACION_TECNICA.md §0.6).
     */
    public static function map(StoredProcedureException $e, array $overrides = []): Throwable
    {
        if (isset($overrides[$e->spCode])) {
            return $overrides[$e->spCode];
        }

        return match ($e->spCode) {
            'NOT_FOUND' => new NotFoundException(),
            'DUPLICATE' => new ConflictException(),
            'CATEGORY_NOT_IN_WORKSPACE' => ValidationException::withMessages([
                'category_id' => ['La categoría no pertenece a este workspace.'],
            ]),
            'PAYER_NOT_IN_WORKSPACE' => ValidationException::withMessages([
                'paid_by_user_id' => ['La persona seleccionada no pertenece a este workspace.'],
            ]),
            'INSUFFICIENT_FUNDS' => new InsufficientFundsException(),
            'INVALID_STATE' => new InvalidStateException(),
            'NEXT_MONTH_CLOSED' => new InvalidStateException(
                'El mes siguiente ya está cerrado; no se puede asignar el sobrante como ingreso ahí.',
            ),
            default => $e,
        };
    }
}
