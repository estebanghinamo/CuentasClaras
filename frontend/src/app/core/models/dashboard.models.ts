import { HealthScoreDto } from './health-score.models';

export interface DashboardCategoryBreakdown {
  category_id: number | null;
  category_name: string;
  category_color: string;
  category_icon: string;
  amount: number;
  pct: number;
}

export interface DashboardUserBreakdown {
  user_id: number;
  user_name: string;
  income: number;
  expenses: number;
  installments: number;
  balance: number;
}

export interface DashboardComparison {
  previous_income: number;
  previous_expenses: number;
  income_delta_pct: number | null;
  expenses_delta_pct: number | null;
}

export interface DashboardOverdueService {
  service_payment_id: number;
  service_name: string;
  amount: number;
  due_date_end: string;
}

export interface DashboardPaidService {
  service_name: string;
  amount: number;
}

export interface DashboardInstallmentBreakdown {
  description: string;
  number: number;
  installments_count: number;
  amount: number;
  status: 'pending' | 'paid';
}

export interface DashboardPendingAllocation {
  year: number;
  month: number;
  unallocated_amount: number;
}

export type DashboardTrend = 'under' | 'on_track' | 'over';

export interface DashboardProjection {
  days_elapsed: number;
  days_in_month: number;
  daily_average: number;
  projected_expenses: number;
  projected_available: number;
  trend: DashboardTrend;
}

export interface HistoryPointDto {
  year: number;
  month: number;
  income: number;
  expenses: number;
  services: number;
  installments: number;
  available: number;
  is_closed: boolean;
}

export interface DashboardDto {
  year: number;
  month: number;
  currency: string;
  totals: {
    income: number;
    expenses: number;
    category_expenses: number;
    services_paid: number;
    installments: number;
    installments_paid: number;
    committed: number;
    available: number;
    savings_deposited: number;
    savings_withdrawn: number;
    goals_contributed: number;
    goals_withdrawn: number;
  };
  by_category: DashboardCategoryBreakdown[];
  paid_services: DashboardPaidService[];
  installment_breakdown: DashboardInstallmentBreakdown[];
  by_user: DashboardUserBreakdown[];
  comparison: DashboardComparison | null;
  overdue_services: { count: number; items: DashboardOverdueService[] };
  /** En vivo para el período mostrado (pasado o actual) - ver DashboardService::get(), null en el mes vacío (sin ingresos ni gastos). */
  health: HealthScoreDto | null;
  pending_allocation: DashboardPendingAllocation | null;
  /** Solo para el período actual (nunca meses pasados/futuros) y con al menos 1 día transcurrido. */
  projection: DashboardProjection | null;
}
