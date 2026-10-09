<?php

namespace App\Http\Requests\Finance;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class CreateIncomeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', Money::make()],
            'concept' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
