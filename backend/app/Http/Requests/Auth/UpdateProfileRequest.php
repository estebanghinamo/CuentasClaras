<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'locale' => ['required', Rule::in(['es', 'en'])],
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
        ];
    }
}
