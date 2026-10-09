import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { CreateSettlementPaymentRequest, SettlementPaymentDto, SettlementSummaryDto } from '../../core/models/settlement.models';

@Injectable({ providedIn: 'root' })
export class SettlementResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}`;
  }

  getSummary(workspaceId: number): Observable<SettlementSummaryDto> {
    return this.http.get<ApiSuccess<SettlementSummaryDto>>(`${this.base(workspaceId)}/settlement`).pipe(map((r) => r.data));
  }

  createPayment(workspaceId: number, request: CreateSettlementPaymentRequest): Observable<SettlementPaymentDto> {
    return this.http
      .post<ApiSuccess<SettlementPaymentDto>>(`${this.base(workspaceId)}/settlement-payments`, request)
      .pipe(map((r) => r.data));
  }

  deletePayment(workspaceId: number, settlementPaymentId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/settlement-payments/${settlementPaymentId}`);
  }
}
