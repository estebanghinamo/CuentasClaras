<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'currency' => ['required', 'string', 'size:3', 'in:'.implode(',', config('currencies'))],
            'type' => ['required', 'in:individual,shared_joint,shared_separate,shared_settlement'],
        ];
    }
}
