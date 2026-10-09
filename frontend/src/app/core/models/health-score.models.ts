export type HealthLevel = 'good' | 'warning' | 'bad';

export interface HealthBudgetsBreakdown {
  score: number;
  weight: number;
  budgets_count: number;
  within_limit_count: number;
  detail: string;
}

export interface HealthSavingsBreakdown {
  score: number;
  weight: number;
  savings_rate_pct: number;
  detail: string;
}

export interface HealthServicesBreakdown {
  score: number;
  weight: number;
  paid_on_time: number;
  paid_late: number;
  overdue: number;
  detail: string;
}

export interface HealthInstallmentsBreakdown {
  score: number;
  weight: number;
  load_pct: number;
  detail: string;
}

export interface HealthBreakdownDto {
  budgets: HealthBudgetsBreakdown;
  savings: HealthSavingsBreakdown;
  services_on_time: HealthServicesBreakdown;
  installments_load: HealthInstallmentsBreakdown;
}

export interface HealthScoreDto {
  year: number;
  month: number;
  score: number;
  level: HealthLevel;
  breakdown: HealthBreakdownDto;
}
