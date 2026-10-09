<?php

namespace App\Http\Requests\Finance;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', Money::make()],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:cash,debit,credit,transfer,wallet,other'],
            'date' => ['required', 'date_format:Y-m-d'],
            'paid_by_user_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
