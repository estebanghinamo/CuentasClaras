<?php

namespace App\Http\Requests\Finance;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;

class PayServicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_paid' => ['nullable', Money::make()],
            'paid_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'apply_late_fee' => ['nullable', 'boolean'],
            // Ajuste manual sobre el recargo calculado (puede ser negativo, ej. si el
            // recargo real cobrado fue menor al calculado) - por eso no usa App\Rules\Money,
            // que no permite negativos.
            'fee_difference' => ['nullable', 'regex:/^-?\d{1,12}(\.\d{1,2})?$/'],
        ];
    }
}
