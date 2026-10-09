import { PaginationMeta } from './api.models';

export type PaymentMethod = 'cash' | 'debit' | 'credit' | 'transfer' | 'wallet' | 'other';

export interface ExpenseDto {
  id: number;
  user_id: number;
  user_name: string;
  category_id: number | null;
  category_name: string | null;
  category_icon: string | null;
  category_color: string | null;
  amount: number;
  description: string | null;
  payment_method: PaymentMethod;
  date: string;
  created_at: string;
  can_edit: boolean;
}

export interface CreateExpenseRequest {
  amount: number;
  category_id: number | null;
  description?: string | null;
  payment_method: PaymentMethod;
  date: string;
}

export type UpdateExpenseRequest = CreateExpenseRequest;

export interface ExpenseFilters {
  page?: number;
  per_page?: number;
  date_from?: string;
  date_to?: string;
  category_ids?: number[];
  user_id?: number;
  payment_method?: PaymentMethod;
  amount_min?: number;
  amount_max?: number;
  search?: string;
  sort?: 'date' | 'amount' | 'created_at';
  order?: 'asc' | 'desc';
}

export type ExpenseListMeta = PaginationMeta & { total_amount: number };

export interface ExpenseListDto {
  items: ExpenseDto[];
  meta: ExpenseListMeta;
}

export const PAYMENT_METHOD_LABELS: Record<PaymentMethod, string> = {
  cash: 'Efectivo',
  debit: 'Débito',
  credit: 'Crédito',
  transfer: 'Transferencia',
  wallet: 'Billetera virtual',
  other: 'Otro',
};
