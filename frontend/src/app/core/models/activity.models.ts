export type AuditAction = 'created' | 'updated' | 'deleted';

export interface AuditLogDto {
  id: number;
  user_id: number;
  user_name: string;
  entity_type: string;
  entity_id: number;
  action: AuditAction;
  summary: string;
  old_value: Record<string, unknown> | null;
  new_value: Record<string, unknown> | null;
  created_at: string;
}

export interface ActivityFilters {
  page?: number;
  per_page?: number;
  entity_type?: string;
  user_id?: number;
}
