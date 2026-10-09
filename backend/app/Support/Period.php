<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class Period
{
    public function __construct(
        public int $year,
        public int $month,
    ) {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Mes inválido: {$month}.");
        }
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException("Año inválido: {$year}.");
        }
    }

    public static function fromDate(string $ymd): self
    {
        $date = CarbonImmutable::createFromFormat('Y-m-d', $ymd);

        return new self((int) $date->year, (int) $date->month);
    }

    public static function fromYearMonth(int $year, int $month): self
    {
        return new self($year, $month);
    }

    public static function current(): self
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        return new self((int) $now->year, (int) $now->month);
    }

    public function previous(): self
    {
        $date = CarbonImmutable::createFromDate($this->year, $this->month, 1)->subMonthNoOverflow();

        return new self((int) $date->year, (int) $date->month);
    }

    public function next(): self
    {
        $date = CarbonImmutable::createFromDate($this->year, $this->month, 1)->addMonthNoOverflow();

        return new self((int) $date->year, (int) $date->month);
    }

    public function equals(Period $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month;
    }

    public function isAfter(Period $other): bool
    {
        return $this->year > $other->year || ($this->year === $other->year && $this->month > $other->month);
    }

    public function firstDay(): string
    {
        return sprintf('%04d-%02d-01', $this->year, $this->month);
    }

    public function lastDay(): string
    {
        return CarbonImmutable::createFromDate($this->year, $this->month, 1)->endOfMonth()->format('Y-m-d');
    }

    public function daysInMonth(): int
    {
        return CarbonImmutable::createFromDate($this->year, $this->month, 1)->daysInMonth;
    }

    public function label(): string
    {
        return sprintf('%02d/%04d', $this->month, $this->year);
    }
}
