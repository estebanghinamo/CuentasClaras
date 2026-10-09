import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { CreateIncomeEntryRequest, IncomeListDto, IncomeEntryDto, UpdateIncomeEntryRequest } from '../../core/models/income.models';

@Injectable({ providedIn: 'root' })
export class IncomeResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/income-entries`;
  }

  list(workspaceId: number, year: number, month: number): Observable<IncomeListDto> {
    const params = new HttpParams().set('year', year).set('month', month);

    return this.http
      .get<ApiSuccess<IncomeListDto>>(this.base(workspaceId), { params })
      .pipe(map((r) => r.data));
  }

  create(workspaceId: number, request: CreateIncomeEntryRequest): Observable<IncomeEntryDto> {
    return this.http.post<ApiSuccess<IncomeEntryDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }

  update(workspaceId: number, incomeEntryId: number, request: UpdateIncomeEntryRequest): Observable<IncomeEntryDto> {
    return this.http
      .put<ApiSuccess<IncomeEntryDto>>(`${this.base(workspaceId)}/${incomeEntryId}`, request)
      .pipe(map((r) => r.data));
  }

  delete(workspaceId: number, incomeEntryId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/${incomeEntryId}`);
  }
}
