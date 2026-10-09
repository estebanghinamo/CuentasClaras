import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess, Paginated } from '../../core/models/api.models';
import { ActivityFilters, AuditLogDto } from '../../core/models/activity.models';

@Injectable({ providedIn: 'root' })
export class ActivityResource {
  private readonly http = inject(HttpClient);

  list(workspaceId: number, filters: ActivityFilters = {}): Observable<Paginated<AuditLogDto>> {
    let params = new HttpParams();
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);
    if (filters.entity_type) params = params.set('entity_type', filters.entity_type);
    if (filters.user_id) params = params.set('user_id', filters.user_id);

    return this.http
      .get<ApiSuccess<AuditLogDto[]>>(`${environment.apiUrl}/workspaces/${workspaceId}/activity`, { params })
      .pipe(map((r) => ({ items: r.data, meta: r.meta! })));
  }
}
