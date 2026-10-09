import { LateFeeType } from './service.models';

export type SuggestionStatus = 'pending' | 'accepted' | 'dismissed';

export interface SmartSuggestionDto {
  id: number;
  description: string;
  avg_amount: number;
  frequency_detected: 'monthly';
  occurrences: number;
  last_seen_date: string;
  suggested_due_day: number;
  status: SuggestionStatus;
  service_id: number | null;
  created_at: string;
}

/** Igual forma que CreateServiceRequest (M-08): aceptar una sugerencia crea un Service real. */
export interface AcceptSuggestionRequest {
  name: string;
  amount: number;
  is_estimated: boolean;
  due_day_start: number;
  due_day_end?: number | null;
  late_fee_type: LateFeeType;
  late_fee_value: number;
}
