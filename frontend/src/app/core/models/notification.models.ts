export type NotificationType =
  | 'service_due_soon'
  | 'service_overdue'
  | 'installment_due_soon'
  | 'budget_alert'
  | 'workspace_invitation'
  | 'workspace_member_joined'
  | 'month_closed'
  | 'savings_goal_completed'
  | 'smart_suggestion';

export interface NotificationDto {
  id: number;
  type: NotificationType;
  title: string;
  body: string;
  route: string | null;
  workspace_id: number | null;
  payload: Record<string, unknown>;
  read_at: string | null;
  created_at: string;
}

export interface NotificationListFilters {
  page?: number;
  per_page?: number;
  unread_only?: boolean;
}

export type BudgetAlertNotificationLevel = 'warning' | 'reached' | 'exceeded';

export interface NotificationPreferencesDto {
  service_reminder_days: number[];
  budget_alert_levels: BudgetAlertNotificationLevel[];
  channels: { in_app: boolean; push: boolean; email: boolean };
  muted_types: NotificationType[];
}

export type PushPlatform = 'android' | 'ios' | 'web';

export interface RegisterPushDeviceRequest {
  token: string;
  platform: PushPlatform;
  device_name?: string | null;
}
