<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PeriodMonth implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value) || (int) $value != $value || (int) $value < 1 || (int) $value > 12) {
            $fail('El mes debe estar entre 1 y 12.');
        }
    }
}
