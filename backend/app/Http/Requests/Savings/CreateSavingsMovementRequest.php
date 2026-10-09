<?php

namespace App\Http\Requests\Savings;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSavingsMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['deposit', 'withdraw'])],
            'amount' => ['required', Money::make()],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
