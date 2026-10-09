<?php

namespace App\Http\Requests\Savings;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSavingsGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'target_amount' => ['required', Money::make()],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after:today'],
        ];
    }
}
