<?php

namespace App\Http\Requests\Sidebar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetSidebarSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sections' => ['present', 'array'],
            'sections.*' => ['string', Rule::in(['budgets', 'savings', 'goals', 'members', 'activity'])],
        ];
    }
}
