import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { CreateServiceRequest, ServiceDto, UpdateServiceRequest } from '../../core/models/service.models';

@Injectable({ providedIn: 'root' })
export class ServicesResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/services`;
  }

  list(workspaceId: number, active: 'all' | 'true' | 'false' = 'true'): Observable<ServiceDto[]> {
    const params = new HttpParams().set('active', active);

    return this.http.get<ApiSuccess<ServiceDto[]>>(this.base(workspaceId), { params }).pipe(map((r) => r.data));
  }

  create(workspaceId: number, request: CreateServiceRequest): Observable<ServiceDto> {
    return this.http.post<ApiSuccess<ServiceDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }

  update(workspaceId: number, serviceId: number, request: UpdateServiceRequest): Observable<ServiceDto> {
    return this.http
      .put<ApiSuccess<ServiceDto>>(`${this.base(workspaceId)}/${serviceId}`, request)
      .pipe(map((r) => r.data));
  }

  delete(workspaceId: number, serviceId: number): Observable<void> {
    return this.http.delete<void>(`${this.base(workspaceId)}/${serviceId}`);
  }
}
