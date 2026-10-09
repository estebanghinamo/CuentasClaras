<?php

namespace App\Http\Requests\Reports;

use App\Rules\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AllocateClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_wallet' => ['required', Money::make()->allowZero()],
            'to_goals' => ['present', 'array', 'max:20'],
            'to_goals.*.goal_id' => ['required', 'integer'],
            'to_goals.*.amount' => ['required', Money::make()],
            'to_next_month' => ['required', Money::make()->allowZero()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();
            $toWallet = (float) ($data['to_wallet'] ?? 0);
            $toNextMonth = (float) ($data['to_next_month'] ?? 0);
            $goalsSum = array_sum(array_column($data['to_goals'] ?? [], 'amount'));

            if (($toWallet + $goalsSum + $toNextMonth) <= 0) {
                $validator->errors()->add(
                    'to_wallet',
                    'Tenés que asignar algo al monedero, a alguna meta o al mes siguiente.',
                );
            }
        });
    }
}
