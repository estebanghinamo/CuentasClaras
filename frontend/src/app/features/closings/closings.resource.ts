import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { AllocateClosingRequest, MonthlyClosingDto } from '../../core/models/closing.models';

@Injectable({ providedIn: 'root' })
export class ClosingsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/closings`;
  }

  /** Lista sin breakdown (el backend lo omite a propósito en sp_monthly_closing_list). */
  list(workspaceId: number): Observable<MonthlyClosingDto[]> {
    return this.http.get<ApiSuccess<MonthlyClosingDto[]>>(this.base(workspaceId)).pipe(map((r) => r.data));
  }

  get(workspaceId: number, year: number, month: number): Observable<MonthlyClosingDto> {
    return this.http
      .get<ApiSuccess<MonthlyClosingDto>>(`${this.base(workspaceId)}/${year}/${month}`)
      .pipe(map((r) => r.data));
  }

  allocate(workspaceId: number, year: number, month: number, request: AllocateClosingRequest): Observable<MonthlyClosingDto> {
    return this.http
      .post<ApiSuccess<MonthlyClosingDto>>(`${this.base(workspaceId)}/${year}/${month}/allocate`, request)
      .pipe(map((r) => r.data));
  }
}
