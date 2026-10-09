<?php

namespace App\Http\Requests\Finance;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'category_ids' => ['nullable', 'array', 'max:20'],
            'category_ids.*' => ['integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'in:cash,debit,credit,transfer,wallet,other'],
            'amount_min' => ['nullable', Money::make()->allowZero()],
            'amount_max' => ['nullable', Money::make()->allowZero(), 'gte:amount_min'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['date', 'amount', 'created_at'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
