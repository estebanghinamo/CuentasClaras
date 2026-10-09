import { Injectable, inject, signal } from '@angular/core';
import { Observable, interval, of, startWith, tap } from 'rxjs';
import { NotificationDto } from '../models/notification.models';
import { NotificationResource } from '../resources/notification.resource';
import { SessionService } from './session.service';

const POLL_MS = 60_000;
const BELL_LIMIT = 8;

/**
 * Version minima de M-17 (solo in-app): campana con badge de no leidas.
 * Polling simple cada 60s mientras haya sesion y la pestaña este visible -
 * sin websockets a proposito (mismo criterio ya documentado para M-17 P-23).
 *
 * `notifications` es SOLO la vista corta para la campana (las ultimas no
 * leidas, ver loadRecentUnread) - el historial completo (leidas incluidas)
 * vive en features/notifications/pages/notifications.page, que pagina contra
 * NotificationResource directo sin pasar por este signal.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly resource = inject(NotificationResource);
  private readonly session = inject(SessionService);

  readonly notifications = signal<NotificationDto[]>([]);
  readonly unreadCount = signal(0);

  constructor() {
    interval(POLL_MS)
      .pipe(startWith(0))
      .subscribe(() => {
        if (this.session.accessToken() && document.visibilityState === 'visible') {
          this.refreshUnreadCount();
        }
      });
  }

  refreshUnreadCount(): void {
    this.resource.unreadCount().subscribe((count) => this.unreadCount.set(count));
  }

  loadRecentUnread(): void {
    this.resource.list({ unread_only: true, per_page: BELL_LIMIT }).subscribe(({ items }) => this.notifications.set(items));
  }

  /** Actualiza unreadCount y saca el item de la vista corta de la campana (si estaba ahi) - el caller (bell o la pagina de historial) se ocupa de su propia lista local con el dto actualizado que devuelve. */
  markRead(notification: NotificationDto): Observable<NotificationDto> {
    if (notification.read_at) {
      return of(notification);
    }

    return this.resource.markRead(notification.id).pipe(
      tap((updated) => {
        this.notifications.update((list) => list.filter((n) => n.id !== updated.id));
        this.unreadCount.update((count) => Math.max(0, count - 1));
      }),
    );
  }

  markAllRead(): Observable<number> {
    return this.resource.markAllRead().pipe(
      tap(() => {
        this.notifications.set([]);
        this.unreadCount.set(0);
      }),
    );
  }
}
