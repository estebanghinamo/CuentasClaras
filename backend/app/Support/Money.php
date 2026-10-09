<?php

namespace App\Support;

/**
 * Aritmética de dinero con bcmath, sobre strings, escala fija en 2 decimales.
 * Nunca usar float para sumar/restar montos (ver ESPECIFICACION_TECNICA.md §0.11).
 */
final class Money
{
    private const SCALE = 2;

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }

    public static function mul(string $a, string $b): string
    {
        return bcmul($a, $b, self::SCALE);
    }

    public static function div(string $a, string $b): string
    {
        return bcdiv($a, $b, self::SCALE);
    }

    public static function cmp(string $a, string $b): int
    {
        return bccomp($a, $b, self::SCALE);
    }

    public static function percent(string $base, string $pct): string
    {
        return bcdiv(bcmul($base, $pct, self::SCALE + 2), '100', self::SCALE);
    }

    public static function toFloat(string $amount): float
    {
        return (float) $amount;
    }

    /** M-25: SettlementCalculator trabaja siempre en centavos enteros, nunca en
     * DECIMAL string, para el redondeo por resto mayor. */
    public static function toCents(string $amount): int
    {
        return (int) bcmul($amount, '100', 0);
    }

    public static function fromCents(int $cents): string
    {
        return bcdiv((string) $cents, '100', self::SCALE);
    }
}
