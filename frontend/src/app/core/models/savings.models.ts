export type SavingsMovementType = 'deposit' | 'withdraw';

export interface SavingsWalletDto {
  id: number;
  balance: number;
  total_deposited: number;
  total_withdrawn: number;
  updated_at: string;
}

export interface SavingsMovementDto {
  id: number;
  type: SavingsMovementType;
  amount: number;
  balance_after: number;
  year: number;
  month: number;
  note: string | null;
  source: 'manual' | 'closing';
  user_name: string | null;
  created_at: string;
}

export interface CreateSavingsMovementRequest {
  type: SavingsMovementType;
  amount: number;
  note?: string | null;
}

export interface SavingsWalletHistoryPoint {
  year: number;
  month: number;
  balance_end: number;
}
