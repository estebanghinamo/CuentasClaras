import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess, Paginated } from '../../core/models/api.models';
import {
  CreateSavingsMovementRequest,
  SavingsMovementDto,
  SavingsWalletDto,
  SavingsWalletHistoryPoint,
} from '../../core/models/savings.models';

@Injectable({ providedIn: 'root' })
export class SavingsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/savings`;
  }

  getWallet(workspaceId: number): Observable<SavingsWalletDto> {
    return this.http.get<ApiSuccess<SavingsWalletDto>>(`${this.base(workspaceId)}/wallet`).pipe(map((r) => r.data));
  }

  getHistory(workspaceId: number, months = 12): Observable<SavingsWalletHistoryPoint[]> {
    const params = new HttpParams().set('months', months);

    return this.http
      .get<ApiSuccess<SavingsWalletHistoryPoint[]>>(`${this.base(workspaceId)}/wallet/history`, { params })
      .pipe(map((r) => r.data));
  }

  listMovements(
    workspaceId: number,
    page = 1,
    perPage = 20,
    type?: 'deposit' | 'withdraw' | null,
  ): Observable<Paginated<SavingsMovementDto>> {
    let params = new HttpParams().set('page', page).set('per_page', perPage);
    if (type) {
      params = params.set('type', type);
    }

    return this.http
      .get<ApiSuccess<SavingsMovementDto[]>>(`${this.base(workspaceId)}/movements`, { params })
      .pipe(map((r) => ({ items: r.data, meta: r.meta! })));
  }

  createMovement(workspaceId: number, request: CreateSavingsMovementRequest): Observable<SavingsMovementDto> {
    return this.http
      .post<ApiSuccess<SavingsMovementDto>>(`${this.base(workspaceId)}/movements`, request)
      .pipe(map((r) => r.data));
  }
}
