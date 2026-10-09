import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatChipsModule } from '@angular/material/chips';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { NotificationResource } from '../../../../core/resources/notification.resource';
import { BudgetAlertNotificationLevel, NotificationPreferencesDto, NotificationType } from '../../../../core/models/notification.models';
import { ToastService } from '../../../../core/services/toast.service';

const BUDGET_ALERT_LEVEL_LABELS: Record<BudgetAlertNotificationLevel, string> = {
  warning: 'Alerta (80%)',
  reached: 'Al llegar al límite',
  exceeded: 'Al superar el límite',
};

const MUTABLE_TYPE_LABELS: Record<NotificationType, string> = {
  service_due_soon: 'Servicio por vencer',
  service_overdue: 'Servicio vencido',
  installment_due_soon: 'Cuotas del mes',
  budget_alert: 'Alerta de presupuesto',
  workspace_invitation: 'Invitación a un espacio',
  workspace_member_joined: 'Nuevo miembro',
  month_closed: 'Cierre mensual',
  savings_goal_completed: 'Meta de ahorro completada',
  smart_suggestion: 'Sugerencia inteligente',
};

/**
 * Preferencias de notificación (M-17): días de recordatorio de servicios,
 * niveles de alerta de presupuesto que notifican, canales (in-app/push/email)
 * y tipos silenciados por completo. Embebido en profile.page, sin ruta propia
 * (mismo criterio que CategoryManagerPanel en Gastos).
 */
@Component({
  selector: 'app-notification-preferences',
  imports: [ReactiveFormsModule, MatButtonModule, MatCheckboxModule, MatChipsModule, MatFormFieldModule, MatIconModule, MatInputModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './notification-preferences.html',
})
export class NotificationPreferences {
  private readonly resource = inject(NotificationResource);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly loading = signal(true);
  readonly saving = signal(false);

  readonly budgetAlertLevelLabels = BUDGET_ALERT_LEVEL_LABELS;
  readonly mutedTypeLabels = MUTABLE_TYPE_LABELS;
  readonly allBudgetAlertLevels: BudgetAlertNotificationLevel[] = ['warning', 'reached', 'exceeded'];
  readonly allNotificationTypes: NotificationType[] = Object.keys(MUTABLE_TYPE_LABELS) as NotificationType[];

  readonly form = this.fb.nonNullable.group({
    service_reminder_days: this.fb.array([this.fb.nonNullable.control(0)]),
    budget_alert_levels: this.fb.nonNullable.control<BudgetAlertNotificationLevel[]>([]),
    channels: this.fb.nonNullable.group({
      in_app: this.fb.nonNullable.control(true),
      push: this.fb.nonNullable.control(true),
      email: this.fb.nonNullable.control(false),
    }),
    muted_types: this.fb.nonNullable.control<NotificationType[]>([]),
  });

  readonly newDayControl = this.fb.control<number | null>(null, [Validators.min(0), Validators.max(30)]);

  constructor() {
    this.resource.getPreferences().subscribe((preferences) => {
      this.applyToForm(preferences);
      this.loading.set(false);
    });
  }

  private applyToForm(preferences: NotificationPreferencesDto): void {
    const days = this.form.controls.service_reminder_days;
    days.clear();
    for (const day of preferences.service_reminder_days) {
      days.push(this.fb.nonNullable.control(day));
    }
    this.form.controls.budget_alert_levels.setValue(preferences.budget_alert_levels);
    this.form.controls.channels.setValue(preferences.channels);
    this.form.controls.muted_types.setValue(preferences.muted_types);
  }

  reminderDays(): number[] {
    return this.form.controls.service_reminder_days.value;
  }

  addReminderDay(): void {
    const day = this.newDayControl.value;
    if (day === null || this.newDayControl.invalid || this.reminderDays().includes(day)) {
      return;
    }
    this.form.controls.service_reminder_days.push(this.fb.nonNullable.control(day));
    this.newDayControl.reset(null);
  }

  removeReminderDay(day: number): void {
    const control = this.form.controls.service_reminder_days;
    const index = control.value.indexOf(day);
    if (index >= 0) {
      control.removeAt(index);
    }
  }

  toggleBudgetAlertLevel(level: BudgetAlertNotificationLevel, checked: boolean): void {
    const control = this.form.controls.budget_alert_levels;
    const current = control.value;
    control.setValue(checked ? [...current, level] : current.filter((l) => l !== level));
  }

  isBudgetAlertLevelChecked(level: BudgetAlertNotificationLevel): boolean {
    return this.form.controls.budget_alert_levels.value.includes(level);
  }

  toggleMutedType(type: NotificationType, muted: boolean): void {
    const control = this.form.controls.muted_types;
    const current = control.value;
    control.setValue(muted ? [...current, type] : current.filter((t) => t !== type));
  }

  isTypeMuted(type: NotificationType): boolean {
    return this.form.controls.muted_types.value.includes(type);
  }

  save(): void {
    if (this.saving()) {
      return;
    }
    this.saving.set(true);
    this.resource.updatePreferences(this.form.getRawValue()).subscribe({
      next: (preferences) => {
        this.saving.set(false);
        this.applyToForm(preferences);
        this.toast.success('Preferencias de notificaciones actualizadas.');
      },
      error: () => {
        this.saving.set(false);
        this.toast.error('No se pudieron guardar las preferencias.');
      },
    });
  }
}
