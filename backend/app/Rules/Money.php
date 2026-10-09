<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida un monto de dinero: numérico, hasta 2 decimales, dentro del rango
 * aceptado por la API (ver ESPECIFICACION_TECNICA.md §0.11).
 */
final class Money implements ValidationRule
{
    private const MAX = '999999999999.99';

    private bool $zeroAllowed = false;

    public static function make(): self
    {
        return new self();
    }

    public function allowZero(): self
    {
        $this->zeroAllowed = true;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('El monto debe ser un número.');

            return;
        }

        $amount = (string) $value;

        if (str_contains($amount, '.')) {
            $decimals = strlen(substr($amount, strpos($amount, '.') + 1));
            if ($decimals > 2) {
                $fail('El monto admite hasta 2 decimales.');

                return;
            }
        }

        $min = $this->zeroAllowed ? '0' : '0.01';
        if (bccomp($amount, $min, 2) < 0) {
            $fail($this->zeroAllowed ? 'El monto no puede ser negativo.' : 'El monto debe ser mayor a 0.');

            return;
        }

        if (bccomp($amount, self::MAX, 2) > 0) {
            $fail('El monto supera el máximo permitido.');
        }
    }
}
