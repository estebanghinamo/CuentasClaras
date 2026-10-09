<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PeriodYear implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value) || (int) $value != $value || (int) $value < 2000 || (int) $value > 2100) {
            $fail('El año debe estar entre 2000 y 2100.');
        }
    }
}
