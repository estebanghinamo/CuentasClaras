import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { DashboardDto, HistoryPointDto } from '../../core/models/dashboard.models';

@Injectable({ providedIn: 'root' })
export class DashboardResource {
  private readonly http = inject(HttpClient);

  get(workspaceId: number, year?: number, month?: number): Observable<DashboardDto> {
    let params = new HttpParams();
    if (year) params = params.set('year', year);
    if (month) params = params.set('month', month);

    return this.http
      .get<ApiSuccess<DashboardDto>>(`${environment.apiUrl}/workspaces/${workspaceId}/dashboard`, { params })
      .pipe(map((r) => r.data));
  }

  history(workspaceId: number, months = 12): Observable<HistoryPointDto[]> {
    const params = new HttpParams().set('months', months);

    return this.http
      .get<ApiSuccess<HistoryPointDto[]>>(`${environment.apiUrl}/workspaces/${workspaceId}/dashboard/history`, { params })
      .pipe(map((r) => r.data));
  }

  exportAll(workspaceId: number): Observable<Blob> {
    return this.http.get(`${environment.apiUrl}/workspaces/${workspaceId}/export`, { responseType: 'blob' });
  }
}
