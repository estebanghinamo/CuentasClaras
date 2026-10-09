export type BudgetAlertLevel = 'none' | 'warning' | 'reached' | 'exceeded';

export interface BudgetDto {
  id: number;
  category_id: number;
  category_name: string;
  category_icon: string;
  category_color: string;
  year: number;
  month: number;
  limit_amount: number;
  spent_amount: number;
  remaining_amount: number;
  progress_pct: number;
  alert_level: BudgetAlertLevel;
}

export interface CategoryWithoutBudgetDto {
  id: number;
  name: string;
  spent_amount: number;
}

export interface BudgetSummaryDto {
  budgets: BudgetDto[];
  total_limit: number;
  total_spent: number;
  categories_without_budget: CategoryWithoutBudgetDto[];
}

export interface UpsertBudgetRequest {
  category_id: number;
  year: number;
  month: number;
  limit_amount: number;
}

export interface CopyBudgetsRequest {
  from_year: number;
  from_month: number;
  to_year: number;
  to_month: number;
  overwrite: boolean;
}
