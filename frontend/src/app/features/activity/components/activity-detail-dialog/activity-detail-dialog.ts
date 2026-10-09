import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { AuditLogDto } from '../../../../core/models/activity.models';
import { userColor, userInitial } from '../../../../shared/utils/user-color.util';

export interface ActivityDetailDialogData {
  entry: AuditLogDto;
}

interface DiffEntry {
  key: string;
  oldValue: unknown;
  newValue: unknown;
}

/** Fallback para un entity_type o campo sin traducción puntual: snake_case -> "Snake case". */
function humanizeKey(key: string): string {
  const words = key.replaceAll('_', ' ');
  return words.charAt(0).toUpperCase() + words.slice(1);
}

const ACTION_LABELS: Record<AuditLogDto['action'], string> = {
  created: 'Creó',
  updated: 'Editó',
  deleted: 'Eliminó',
};

/** Los 16 entity_type reales (ver backend/app/Services/Audit/AuditSummaries.php, fuente de verdad). */
const ENTITY_TYPE_LABELS: Record<string, string> = {
  workspace: 'Espacio',
  workspace_member: 'Miembro',
  workspace_invitation: 'Invitación',
  category: 'Categoría',
  monthly_closing: 'Cierre mensual',
  smart_suggestion: 'Sugerencia',
  income_entry: 'Ingreso',
  expense: 'Gasto',
  service: 'Servicio',
  service_payment: 'Pago de servicio',
  installment: 'Compra en cuotas',
  installment_payment: 'Cuota',
  savings_movement: 'Movimiento del monedero',
  savings_goal: 'Meta de ahorro',
  budget: 'Presupuesto',
  settlement_payment: 'Pago de liquidación',
};

/** Nombres de campo que aparecen en old_value/new_value en toda la app (auditoría de cualquier entidad). */
const FIELD_LABELS: Record<string, string> = {
  name: 'Nombre',
  description: 'Descripción',
  concept: 'Concepto',
  amount: 'Monto',
  amount_paid: 'Monto pagado',
  category: 'Categoría',
  category_id: 'Categoría',
  icon: 'Ícono',
  color: 'Color',
  currency: 'Moneda',
  type: 'Tipo',
  role: 'Rol',
  email: 'Email',
  code: 'Código',
  expires_at: 'Vence',
  period: 'Período',
  limit_amount: 'Límite',
  target_amount: 'Monto objetivo',
  current_amount: 'Monto acumulado',
  due_date: 'Vencimiento',
  due_date_start: 'Vencimiento (desde)',
  due_date_end: 'Vencimiento (hasta)',
  due_day_start: 'Día de vencimiento (desde)',
  due_day_end: 'Día de vencimiento (hasta)',
  late_fee_type: 'Tipo de recargo',
  late_fee_value: 'Recargo',
  late_fee_applied: 'Recargo aplicado',
  is_estimated: 'Estimado',
  active: 'Activo',
  payment_method: 'Medio de pago',
  paid_by_user_id: 'Pagado por',
  allocated_to_wallet: 'Asignado al monedero',
  allocated_to_goals: 'Asignado a metas',
  allocated_to_next_month: 'Asignado al mes siguiente',
  number: 'Número',
  installments_count: 'Cantidad de cuotas',
  workspace_name: 'Espacio',
  from_name: 'De',
  to_name: 'Para',
  from_user_id: 'De (usuario)',
  to_user_id: 'Para (usuario)',
  user_id: 'Usuario',
  note: 'Nota',
  notes: 'Nota',
  status: 'Estado',
  balance: 'Saldo',
  wallet_balance: 'Saldo del monedero',
  start_date: 'Fecha de inicio',
  month: 'Mes',
  year: 'Año',
  was_late: 'Con mora',
  total_amount: 'Monto total',
  goal_amount: 'Monto de la meta',
};

@Component({
  selector: 'app-activity-detail-dialog',
  imports: [MatDialogModule, MatButtonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './activity-detail-dialog.html',
  styleUrl: './activity-detail-dialog.scss',
})
export class ActivityDetailDialog {
  protected readonly data = inject<ActivityDetailDialogData>(MAT_DIALOG_DATA);

  protected readonly userColor = userColor(this.data.entry.user_id);
  protected readonly userInitial = userInitial(this.data.entry.user_name);
  protected readonly actionLabel = ACTION_LABELS[this.data.entry.action];
  protected readonly entityTypeLabel = ActivityDetailDialog.entityTypeLabel(this.data.entry.entity_type);

  protected readonly diff: DiffEntry[] = this.buildDiff();

  formatDate(iso: string): string {
    return new Date(iso).toLocaleString('es-AR');
  }

  fieldLabel(key: string): string {
    return FIELD_LABELS[key] ?? humanizeKey(key);
  }

  private static entityTypeLabel(entityType: string): string {
    return ENTITY_TYPE_LABELS[entityType] ?? humanizeKey(entityType);
  }

  formatValue(value: unknown): string {
    if (value === null || value === undefined) {
      return '—';
    }
    if (typeof value === 'object') {
      return JSON.stringify(value);
    }
    if (typeof value === 'number' || typeof value === 'boolean') {
      return String(value);
    }
    return value as string;
  }

  private buildDiff(): DiffEntry[] {
    const oldValue = this.data.entry.old_value ?? {};
    const newValue = this.data.entry.new_value ?? {};
    const keys = new Set([...Object.keys(oldValue), ...Object.keys(newValue)]);

    return [...keys].map((key) => ({ key, oldValue: oldValue[key], newValue: newValue[key] }));
  }
}
