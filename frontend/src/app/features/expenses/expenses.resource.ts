import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import {
  CreateExpenseRequest,
  ExpenseDto,
  ExpenseFilters,
  ExpenseListDto,
  PaymentMethod,
  UpdateExpenseRequest,
} from '../../core/models/expense.models';

@Injectable({ providedIn: 'root' })
export class ExpensesResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/expenses`;
  }

  list(workspaceId: number, filters: ExpenseFilters): Observable<ExpenseListDto> {
    let params = new HttpParams();
    for (const [key, value] of Object.entries(filters)) {
      if (value === undefined || value === null || value === '') {
        continue;
      }
      if (Array.isArray(value)) {
        for (const item of value) {
          params = params.append(`${key}[]`, item);
        }
      } else {
        params = params.set(key, String(value));
      }
    }

    return this.http.get<ApiSuccess<ExpenseDto[]>>(this.base(workspaceId), { params }).pipe(
      map((r) => ({ items: r.data, meta: r.meta as ExpenseListDto['meta'] })),
    );
  }

  get(workspaceId: number, expenseId: number): Observable<ExpenseDto> {
    return this.http.get<ApiSuccess<ExpenseDto>>(`${this.base(workspaceId)}/${expenseId}`).pipe(map((r) => r.data));
  }

  create(workspaceId: number, request: CreateExpenseRequest): Observable<ExpenseDto> {
    return this.http.post<ApiSuccess<ExpenseDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }

  update(workspaceId: number, expenseId: number, request: UpdateExpenseRequest): Observable<ExpenseDto> {
    return this.http
      .put<ApiSuccess<ExpenseDto>>(`${this.base(workspaceId)}/${expenseId}`, request)
      .pipe(map((r) => r.data));
  }

  delete(workspaceId: number, expenseId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/${expenseId}`);
  }

  paymentMethodsUsed(workspaceId: number): Observable<PaymentMethod[]> {
    return this.http
      .get<ApiSuccess<PaymentMethod[]>>(`${this.base(workspaceId)}/payment-methods`)
      .pipe(map((r) => r.data));
  }
}
