export type LateFeeType = 'percentage' | 'fixed';
export type ServicePaymentStatus = 'pending' | 'paid' | 'overdue';

export interface ServiceDto {
  id: number;
  name: string;
  amount: number;
  is_estimated: boolean;
  due_day_start: number;
  due_day_end: number | null;
  late_fee_type: LateFeeType;
  late_fee_value: number;
  active: boolean;
  created_at: string;
}

export interface CreateServiceRequest {
  name: string;
  amount: number;
  is_estimated: boolean;
  due_day_start: number;
  due_day_end?: number | null;
  late_fee_type: LateFeeType;
  late_fee_value: number;
}

export type UpdateServiceRequest = CreateServiceRequest & { active: boolean };

export interface ServicePaymentDto {
  id: number;
  service_id: number;
  service_name: string;
  year: number;
  month: number;
  expected_amount: number;
  due_date_start: string;
  due_date_end: string;
  status: ServicePaymentStatus;
  amount_paid: number | null;
  late_fee_applied: number;
  paid_at: string | null;
  was_late: boolean;
  paid_by_user_id: number | null;
  paid_by_name: string | null;
  notes: string | null;
  days_until_due: number | null;
}

export interface PayServicePaymentRequest {
  amount_paid?: number | null;
  paid_at?: string | null;
  apply_late_fee?: boolean;
  fee_difference?: number | null;
}

export interface ServicePaymentListDto {
  items: ServicePaymentDto[];
  total_expected: number;
  total_paid: number;
  pending_count: number;
  overdue_count: number;
}
