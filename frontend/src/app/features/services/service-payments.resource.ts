import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { PayServicePaymentRequest, ServicePaymentDto, ServicePaymentListDto } from '../../core/models/service.models';

@Injectable({ providedIn: 'root' })
export class ServicePaymentsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/service-payments`;
  }

  listForPeriod(workspaceId: number, year: number, month: number): Observable<ServicePaymentListDto> {
    const params = new HttpParams().set('year', year).set('month', month);

    return this.http
      .get<ApiSuccess<ServicePaymentListDto>>(this.base(workspaceId), { params })
      .pipe(map((r) => r.data));
  }

  pay(workspaceId: number, servicePaymentId: number, request: PayServicePaymentRequest): Observable<ServicePaymentDto> {
    return this.http
      .post<ApiSuccess<ServicePaymentDto>>(`${this.base(workspaceId)}/${servicePaymentId}/pay`, request)
      .pipe(map((r) => r.data));
  }

  unpay(workspaceId: number, servicePaymentId: number): Observable<ServicePaymentDto> {
    return this.http
      .post<ApiSuccess<ServicePaymentDto>>(`${this.base(workspaceId)}/${servicePaymentId}/unpay`, {})
      .pipe(map((r) => r.data));
  }

  overdue(workspaceId: number): Observable<ServicePaymentDto[]> {
    return this.http
      .get<ApiSuccess<ServicePaymentDto[]>>(`${this.base(workspaceId)}/overdue`)
      .pipe(map((r) => r.data));
  }
}
