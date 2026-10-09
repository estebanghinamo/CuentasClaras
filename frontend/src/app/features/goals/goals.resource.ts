import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import {
  ContributeSavingsGoalRequest,
  CreateSavingsGoalRequest,
  SavingsGoalDto,
  SavingsGoalMovementDto,
  SavingsGoalStatus,
  TransferToGoalRequest,
  TransferToGoalResult,
  UpdateSavingsGoalRequest,
} from '../../core/models/savings-goal.models';

@Injectable({ providedIn: 'root' })
export class GoalsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/savings/goals`;
  }

  list(workspaceId: number, status: SavingsGoalStatus | 'all' = 'active'): Observable<SavingsGoalDto[]> {
    const params = new HttpParams().set('status', status);

    return this.http.get<ApiSuccess<SavingsGoalDto[]>>(this.base(workspaceId), { params }).pipe(map((r) => r.data));
  }

  create(workspaceId: number, request: CreateSavingsGoalRequest): Observable<SavingsGoalDto> {
    return this.http.post<ApiSuccess<SavingsGoalDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }

  update(workspaceId: number, goalId: number, request: UpdateSavingsGoalRequest): Observable<SavingsGoalDto> {
    return this.http.put<ApiSuccess<SavingsGoalDto>>(`${this.base(workspaceId)}/${goalId}`, request).pipe(map((r) => r.data));
  }

  cancel(workspaceId: number, goalId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/${goalId}`);
  }

  contribute(workspaceId: number, goalId: number, request: ContributeSavingsGoalRequest): Observable<SavingsGoalDto> {
    return this.http
      .post<ApiSuccess<SavingsGoalDto>>(`${this.base(workspaceId)}/${goalId}/contribute`, request)
      .pipe(map((r) => r.data));
  }

  movements(workspaceId: number, goalId: number): Observable<SavingsGoalMovementDto[]> {
    return this.http
      .get<ApiSuccess<SavingsGoalMovementDto[]>>(`${this.base(workspaceId)}/${goalId}/movements`)
      .pipe(map((r) => r.data));
  }

  transferFromWallet(workspaceId: number, goalId: number, request: TransferToGoalRequest): Observable<TransferToGoalResult> {
    return this.http
      .post<ApiSuccess<TransferToGoalResult>>(`${this.base(workspaceId)}/${goalId}/transfer-from-wallet`, request)
      .pipe(map((r) => r.data));
  }
}
