import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { BudgetDto, BudgetSummaryDto, CopyBudgetsRequest, UpsertBudgetRequest } from '../../core/models/budget.models';
import { ApiSuccess } from '../../core/models/api.models';

@Injectable({ providedIn: 'root' })
export class BudgetsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/budgets`;
  }

  list(workspaceId: number, year: number, month: number): Observable<BudgetSummaryDto> {
    const params = new HttpParams().set('year', year).set('month', month);

    return this.http.get<ApiSuccess<BudgetSummaryDto>>(this.base(workspaceId), { params }).pipe(map((r) => r.data));
  }

  upsert(workspaceId: number, request: UpsertBudgetRequest): Observable<BudgetDto> {
    return this.http.put<ApiSuccess<BudgetDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }

  delete(workspaceId: number, budgetId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/${budgetId}`);
  }

  copyFromPeriod(workspaceId: number, request: CopyBudgetsRequest): Observable<{ copied_count: number }> {
    return this.http
      .post<ApiSuccess<{ copied_count: number }>>(`${this.base(workspaceId)}/copy`, request)
      .pipe(map((r) => r.data));
  }
}
