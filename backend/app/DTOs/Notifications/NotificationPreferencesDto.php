<?php

namespace App\DTOs\Notifications;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NotificationPreferences',
    required: ['service_reminder_days', 'budget_alert_levels', 'channels', 'muted_types'],
    properties: [
        new OA\Property(
            property: 'service_reminder_days',
            type: 'array',
            items: new OA\Items(type: 'integer', minimum: 0, maximum: 30),
            example: [3, 1],
        ),
        new OA\Property(
            property: 'budget_alert_levels',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['warning', 'reached', 'exceeded']),
            example: ['warning', 'reached', 'exceeded'],
        ),
        new OA\Property(
            property: 'channels',
            required: ['in_app', 'push', 'email'],
            properties: [
                new OA\Property(property: 'in_app', type: 'boolean', example: true),
                new OA\Property(property: 'push', type: 'boolean', example: true),
                new OA\Property(property: 'email', type: 'boolean', example: false),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'muted_types',
            type: 'array',
            items: new OA\Items(
                type: 'string',
                enum: [
                    'service_due_soon', 'service_overdue', 'installment_due_soon', 'budget_alert',
                    'workspace_invitation', 'workspace_member_joined', 'month_closed',
                    'savings_goal_completed', 'smart_suggestion',
                ],
            ),
        ),
    ],
    type: 'object',
)]
final readonly class NotificationPreferencesDto
{
    public function __construct(
        public array $serviceReminderDays,
        public array $budgetAlertLevels,
        public array $channels,
        public array $mutedTypes,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            serviceReminderDays: $row['service_reminder_days'] ?? [3, 1],
            budgetAlertLevels: $row['budget_alert_levels'] ?? ['warning', 'reached', 'exceeded'],
            channels: $row['channels'] ?? ['in_app' => true, 'push' => true, 'email' => false],
            mutedTypes: $row['muted_types'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'service_reminder_days' => $this->serviceReminderDays,
            'budget_alert_levels' => $this->budgetAlertLevels,
            'channels' => $this->channels,
            'muted_types' => $this->mutedTypes,
        ];
    }
}
