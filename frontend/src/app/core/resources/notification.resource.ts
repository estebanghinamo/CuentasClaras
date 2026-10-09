import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { skipLoading } from '../interceptors/skip-loading.token';
import { ApiSuccess, Paginated } from '../models/api.models';
import { NotificationDto, NotificationListFilters, NotificationPreferencesDto } from '../models/notification.models';

@Injectable({ providedIn: 'root' })
export class NotificationResource {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/notifications`;

  list(filters: NotificationListFilters = {}): Observable<Paginated<NotificationDto>> {
    let params = new HttpParams();
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);
    if (filters.unread_only) params = params.set('unread_only', '1');

    return this.http
      .get<ApiSuccess<NotificationDto[]>>(this.base, { params })
      .pipe(map((r) => ({ items: r.data, meta: r.meta! })));
  }

  /** Sin loader global a propósito: se llama por polling cada 60s (ver NotificationService), mostrar el overlay en cada tick es molesto y el usuario no inició esta acción. */
  unreadCount(): Observable<number> {
    return this.http
      .get<ApiSuccess<{ count: number }>>(`${this.base}/unread-count`, { context: skipLoading() })
      .pipe(map((r) => r.data.count));
  }

  markRead(notificationId: number): Observable<NotificationDto> {
    return this.http
      .post<ApiSuccess<NotificationDto>>(`${this.base}/${notificationId}/read`, {})
      .pipe(map((r) => r.data));
  }

  markAllRead(): Observable<number> {
    return this.http
      .post<ApiSuccess<{ updated_count: number }>>(`${this.base}/read-all`, {})
      .pipe(map((r) => r.data.updated_count));
  }

  getPreferences(): Observable<NotificationPreferencesDto> {
    return this.http.get<ApiSuccess<NotificationPreferencesDto>>(`${this.base}/preferences`).pipe(map((r) => r.data));
  }

  updatePreferences(preferences: NotificationPreferencesDto): Observable<NotificationPreferencesDto> {
    return this.http
      .put<ApiSuccess<NotificationPreferencesDto>>(`${this.base}/preferences`, preferences)
      .pipe(map((r) => r.data));
  }
}
