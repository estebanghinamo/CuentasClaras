export interface PaginationMeta {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface ApiSuccess<T> {
  success: true;
  data: T;
  meta?: PaginationMeta;
}

export interface ApiError {
  success: false;
  error: {
    code: string;
    message: string;
    details: Record<string, string[]> | null;
  };
}

export interface Paginated<T> {
  items: T[];
  meta: PaginationMeta;
}
