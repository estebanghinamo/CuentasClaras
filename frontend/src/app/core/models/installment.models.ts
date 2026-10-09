export type InstallmentStatus = 'active' | 'completed' | 'cancelled';
export type InstallmentPaymentStatus = 'pending' | 'paid';

export interface InstallmentPaymentDto {
  id: number;
  number: number;
  year: number;
  month: number;
  amount: number;
  status: InstallmentPaymentStatus;
  paid_at: string | null;
}

export interface InstallmentDto {
  id: number;
  user_id: number;
  user_name: string;
  description: string;
  category_id: number | null;
  category_name: string | null;
  total_amount: number;
  installments_count: number;
  installment_amount: number;
  start_date: string;
  status: InstallmentStatus;
  paid_count: number;
  remaining_count: number;
  remaining_amount: number;
  next_due: { year: number; month: number } | null;
  can_edit: boolean;
  payments?: InstallmentPaymentDto[] | null;
}

export interface CreateInstallmentRequest {
  description: string;
  total_amount: number;
  installments_count: number;
  start_date: string;
  category_id: number | null;
}

export interface UpdateInstallmentRequest {
  description: string;
  category_id: number | null;
}
