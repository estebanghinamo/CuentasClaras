export interface SettlementBalanceDto {
  user_id: number;
  user_name: string;
  paid: number;
  share: number;
  balance: number;
}

export interface SettlementTransferDto {
  from_user_id: number;
  from_user_name: string;
  to_user_id: number;
  to_user_name: string;
  amount: number;
}

export interface SettlementPaymentDto {
  id: number;
  from_user_id: number;
  from_user_name: string;
  to_user_id: number;
  to_user_name: string;
  amount: number;
  note: string | null;
  registered_by: number;
  registered_by_name: string;
  paid_at: string;
  created_at: string;
}

export interface SettlementSummaryDto {
  total_expenses: number;
  member_count: number;
  share_per_person: number;
  balances: SettlementBalanceDto[];
  pending_settlements: SettlementTransferDto[];
  settled_payments: SettlementPaymentDto[];
}

export interface CreateSettlementPaymentRequest {
  from_user_id: number;
  to_user_id: number;
  amount: number;
  note?: string | null;
}
