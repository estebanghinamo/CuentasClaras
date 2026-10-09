<?php

namespace App\Http\Requests\Finance;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class CreateInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', Money::make()],
            'installments_count' => ['required', 'integer', 'min:1', 'max:120'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'category_id' => ['nullable', 'integer'],
        ];
    }
}
