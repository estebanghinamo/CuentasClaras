<?php

namespace App\Http\Requests\Finance;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', Money::make()],
            'is_estimated' => ['required', 'boolean'],
            'due_day_start' => ['required', 'integer', 'min:1', 'max:31'],
            'due_day_end' => ['nullable', 'integer', 'min:1', 'max:31', 'gte:due_day_start'],
            'late_fee_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'late_fee_value' => [
                'required',
                Money::make()->allowZero(),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('late_fee_type') === 'percentage' && bccomp((string) $value, '100', 2) > 0) {
                        $fail('El recargo no puede superar el 100%.');
                    }
                },
            ],
        ];
    }
}
