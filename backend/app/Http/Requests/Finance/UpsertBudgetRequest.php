<?php

namespace App\Http\Requests\Finance;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class UpsertBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'limit_amount' => ['required', Money::make()],
        ];
    }
}
