import { DatePipe, Location } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { PaginationMeta } from '../../../../core/models/api.models';
import { NotificationDto, NotificationType } from '../../../../core/models/notification.models';
import { NotificationResource } from '../../../../core/resources/notification.resource';
import { NotificationService } from '../../../../core/services/notification.service';
import { Spinner } from '../../../../shared/components/spinner/spinner';

const TYPE_ICONS: Record<NotificationType, string> = {
  service_due_soon: 'receipt_long',
  service_overdue: 'error',
  installment_due_soon: 'credit_card',
  budget_alert: 'pie_chart',
  workspace_invitation: 'mail',
  workspace_member_joined: 'group',
  month_closed: 'event_available',
  savings_goal_completed: 'flag',
  smart_suggestion: 'lightbulb',
};

/** Historial completo (leidas incluidas), a diferencia de la campana que solo muestra las ultimas no leidas. */
@Component({
  selector: 'app-notifications-page',
  imports: [MatButtonModule, Spinner, DatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './notifications.page.html',
  styleUrl: './notifications.page.scss',
})
export class NotificationsPage {
  private readonly resource = inject(NotificationResource);
  protected readonly notificationService = inject(NotificationService);
  private readonly router = inject(Router);
  private readonly location = inject(Location);

  readonly items = signal<NotificationDto[]>([]);
  readonly meta = signal<PaginationMeta | null>(null);
  readonly loading = signal(false);

  constructor() {
    this.fetch(1);
  }

  goBack(): void {
    // La campana abre esta página desde cualquier shell (general o de un
    // workspace puntual) - Location.back() vuelve a donde el usuario estaba
    // realmente, en vez de forzar una ruta fija que no siempre coincide (a
    // pedido del usuario: "te deja como en un punto muerto").
    this.location.back();
  }

  typeIcon(type: NotificationType): string {
    return TYPE_ICONS[type] ?? 'notifications';
  }

  open(notification: NotificationDto): void {
    this.notificationService.markRead(notification).subscribe((updated) => {
      this.items.update((list) => list.map((n) => (n.id === updated.id ? updated : n)));
    });

    if (notification.route) {
      this.router.navigateByUrl(notification.route);
    }
  }

  markAllRead(): void {
    this.notificationService.markAllRead().subscribe(() => {
      const now = new Date().toISOString();
      this.items.update((list) => list.map((n) => (n.read_at ? n : { ...n, read_at: now })));
    });
  }

  loadMore(): void {
    const currentMeta = this.meta();
    if (!currentMeta || this.loading()) {
      return;
    }
    this.fetch(currentMeta.page + 1);
  }

  private fetch(page: number): void {
    this.loading.set(true);
    this.resource.list({ page, per_page: 20 }).subscribe({
      next: ({ items, meta }) => {
        this.loading.set(false);
        this.items.update((current) => (page === 1 ? items : [...current, ...items]));
        this.meta.set(meta);
      },
      error: () => this.loading.set(false),
    });
  }
}
