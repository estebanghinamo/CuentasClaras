export interface CategoryDto {
  id: number;
  name: string;
  icon: string;
  color: string;
  is_default: boolean;
  expenses_count: number;
}

export interface CreateCategoryRequest {
  name: string;
  icon: string;
  color: string;
}

export type UpdateCategoryRequest = CreateCategoryRequest;
