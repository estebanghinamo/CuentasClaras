import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { MatBadgeModule } from '@angular/material/badge';
import { MatButtonModule } from '@angular/material/button';
import { MatMenuModule } from '@angular/material/menu';
import { NotificationDto } from '../../../core/models/notification.models';
import { NotificationService } from '../../../core/services/notification.service';

@Component({
  selector: 'app-notification-bell',
  imports: [MatBadgeModule, MatButtonModule, MatMenuModule, RouterLink, DatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './notification-bell.html',
  styleUrl: './notification-bell.scss',
})
export class NotificationBell {
  protected readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);

  open(notification: NotificationDto): void {
    this.notifications.markRead(notification).subscribe();
    if (notification.route) {
      this.router.navigateByUrl(notification.route);
    }
  }
}
