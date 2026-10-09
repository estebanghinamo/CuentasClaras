export interface IncomeEntryDto {
  id: number;
  user_id: number;
  user_name: string;
  amount: number;
  concept: string;
  date: string;
  year: number;
  month: number;
  source: 'manual' | 'closing';
  created_at: string;
  can_edit: boolean;
}

export interface CreateIncomeEntryRequest {
  amount: number;
  concept: string;
  date: string;
}

export type UpdateIncomeEntryRequest = CreateIncomeEntryRequest;

export interface IncomeByUser {
  user_id: number;
  user_name: string;
  total: number;
}

export interface IncomeListDto {
  entries: IncomeEntryDto[];
  total: number;
  by_user: IncomeByUser[];
}
