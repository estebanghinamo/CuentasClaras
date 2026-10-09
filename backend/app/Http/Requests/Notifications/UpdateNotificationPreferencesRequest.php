<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_reminder_days' => ['present', 'array', 'max:5'],
            'service_reminder_days.*' => ['integer', 'min:0', 'max:30', 'distinct'],
            'budget_alert_levels' => ['present', 'array'],
            'budget_alert_levels.*' => [Rule::in(['warning', 'reached', 'exceeded'])],
            'channels' => ['required', 'array'],
            'channels.in_app' => ['required', 'boolean'],
            'channels.push' => ['required', 'boolean'],
            'channels.email' => ['required', 'boolean'],
            'muted_types' => ['present', 'array'],
            'muted_types.*' => [Rule::in([
                'service_due_soon', 'service_overdue', 'installment_due_soon', 'budget_alert',
                'workspace_invitation', 'workspace_member_joined', 'month_closed',
                'savings_goal_completed', 'smart_suggestion',
            ])],
        ];
    }
}
