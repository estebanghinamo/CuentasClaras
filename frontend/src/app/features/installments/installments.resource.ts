import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import {
  CreateInstallmentRequest,
  InstallmentDto,
  InstallmentPaymentDto,
  UpdateInstallmentRequest,
} from '../../core/models/installment.models';

@Injectable({ providedIn: 'root' })
export class InstallmentsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/installments`;
  }

  list(workspaceId: number, status: 'active' | 'completed' | 'cancelled' | 'all' = 'active'): Observable<InstallmentDto[]> {
    const params = new HttpParams().set('status', status);

    return this.http.get<ApiSuccess<InstallmentDto[]>>(this.base(workspaceId), { params }).pipe(map((r) => r.data));
  }

  get(workspaceId: number, installmentId: number): Observable<InstallmentDto> {
    return this.http.get<ApiSuccess<InstallmentDto>>(`${this.base(workspaceId)}/${installmentId}`).pipe(map((r) => r.data));
  }

  create(workspaceId: number, request: CreateInstallmentRequest): Observable<InstallmentDto> {
    return this.http.post<ApiSuccess<InstallmentDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }

  update(workspaceId: number, installmentId: number, request: UpdateInstallmentRequest): Observable<InstallmentDto> {
    return this.http.put<ApiSuccess<InstallmentDto>>(`${this.base(workspaceId)}/${installmentId}`, request).pipe(map((r) => r.data));
  }

  delete(workspaceId: number, installmentId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/${installmentId}`);
  }

  pay(workspaceId: number, installmentId: number, paymentId: number, paidAt?: string): Observable<InstallmentPaymentDto> {
    return this.http
      .post<ApiSuccess<InstallmentPaymentDto>>(`${this.base(workspaceId)}/${installmentId}/payments/${paymentId}/pay`, { paid_at: paidAt })
      .pipe(map((r) => r.data));
  }

  unpay(workspaceId: number, installmentId: number, paymentId: number): Observable<InstallmentPaymentDto> {
    return this.http
      .post<ApiSuccess<InstallmentPaymentDto>>(`${this.base(workspaceId)}/${installmentId}/payments/${paymentId}/unpay`, {})
      .pipe(map((r) => r.data));
  }
}
