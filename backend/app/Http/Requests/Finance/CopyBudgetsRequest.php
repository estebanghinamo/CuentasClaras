<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CopyBudgetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'from_month' => ['required', 'integer', 'min:1', 'max:12'],
            'to_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'to_month' => ['required', 'integer', 'min:1', 'max:12'],
            'overwrite' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();

            if (($data['from_year'] ?? null) === ($data['to_year'] ?? null)
                && ($data['from_month'] ?? null) === ($data['to_month'] ?? null)) {
                $validator->errors()->add('to_month', 'El período de destino debe ser distinto del de origen.');
            }
        });
    }
}
