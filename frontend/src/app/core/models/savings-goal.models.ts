export type SavingsGoalStatus = 'active' | 'completed' | 'cancelled';
export type SavingsGoalMovementType = 'contribution' | 'withdrawal';

export interface SavingsGoalDto {
  id: number;
  name: string;
  target_amount: number;
  current_amount: number;
  due_date: string | null;
  status: SavingsGoalStatus;
  progress_pct: number;
  remaining_amount: number;
  days_left: number | null;
  suggested_monthly: number | null;
  completed_at: string | null;
  created_at: string;
}

export interface CreateSavingsGoalRequest {
  name: string;
  target_amount: number;
  due_date?: string | null;
}

export type UpdateSavingsGoalRequest = CreateSavingsGoalRequest;

export interface ContributeSavingsGoalRequest {
  type: SavingsGoalMovementType;
  amount: number;
  note?: string | null;
}

export interface SavingsGoalMovementDto {
  id: number;
  type: SavingsGoalMovementType;
  amount: number;
  source: 'manual' | 'closing';
  note: string | null;
  user_name: string | null;
  created_at: string;
}

export interface TransferToGoalRequest {
  amount: number;
  note?: string | null;
}

export interface TransferToGoalResult {
  wallet_balance: number;
  goal: SavingsGoalDto;
}
