<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:150'],
            'token' => ['required', 'string'],
            'password' => [
                'required', 'string', 'min:8', 'max:72', 'confirmed', Password::min(8)->letters()->numbers(),
            ],
        ];
    }
}
