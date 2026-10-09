import { ServicePaymentStatus } from './service.models';

export type AllocationStatus = 'pending' | 'allocated' | 'not_applicable';

export interface ClosingBreakdownDto {
  by_category: { category_id: number | null; category_name: string; amount: number; pct: number }[];
  by_user: { user_id: number; user_name: string; income: number; expenses: number; installments: number }[];
  services: {
    service_id: number;
    name: string;
    status: ServicePaymentStatus;
    amount_paid: number | null;
    late_fee_applied: number;
    was_late: boolean;
  }[];
  installments: { installment_id: number; description: string; number: number; installments_count: number; amount: number }[];
  budgets: { category_id: number; category_name: string; limit_amount: number; spent_amount: number; pct: number }[];
  expenses_count: number;
  income_entries_count: number;
}

export interface MonthlyClosingDto {
  id: number;
  year: number;
  month: number;
  total_income: number;
  total_expenses: number;
  total_services: number;
  total_installments: number;
  remaining_amount: number;
  savings_generated: number;
  allocated_to_wallet: number;
  allocated_to_goals: number;
  allocated_to_next_month: number;
  unallocated_amount: number;
  allocation_status: AllocationStatus;
  allocated_at: string | null;
  closed_by: string;
  closed_at: string;
  health_score: number | null;
  /** Solo viene poblado en el detalle (GET /closings/{year}/{month}), no en la lista. */
  breakdown: ClosingBreakdownDto | null;
  /** false si el mes siguiente ya está cerrado (no admite más ingresos ahí) - ver allocate-dialog. */
  next_month_open: boolean;
}

export interface AllocateClosingRequest {
  to_wallet: number;
  to_goals: { goal_id: number; amount: number }[];
  to_next_month: number;
}
