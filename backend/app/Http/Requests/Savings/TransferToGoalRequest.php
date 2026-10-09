<?php

namespace App\Http\Requests\Savings;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class TransferToGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', Money::make()],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
