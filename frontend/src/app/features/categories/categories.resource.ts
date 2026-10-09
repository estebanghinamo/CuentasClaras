import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { CategoryDto, CreateCategoryRequest, UpdateCategoryRequest } from '../../core/models/category.models';

@Injectable({ providedIn: 'root' })
export class CategoriesResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/categories`;
  }

  list(workspaceId: number): Observable<CategoryDto[]> {
    return this.http.get<ApiSuccess<CategoryDto[]>>(this.base(workspaceId)).pipe(map((r) => r.data));
  }

  create(workspaceId: number, request: CreateCategoryRequest): Observable<CategoryDto> {
    return this.http.post<ApiSuccess<CategoryDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }

  update(workspaceId: number, categoryId: number, request: UpdateCategoryRequest): Observable<CategoryDto> {
    return this.http
      .put<ApiSuccess<CategoryDto>>(`${this.base(workspaceId)}/${categoryId}`, request)
      .pipe(map((r) => r.data));
  }

  delete(workspaceId: number, categoryId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/${categoryId}`);
  }
}
