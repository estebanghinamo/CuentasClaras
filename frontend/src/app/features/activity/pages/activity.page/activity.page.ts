import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { AuditLogDto } from '../../../../core/models/activity.models';
import { PaginationMeta } from '../../../../core/models/api.models';
import { userColor, userInitial } from '../../../../shared/utils/user-color.util';
import { ActivityDetailDialog, ActivityDetailDialogData } from '../../components/activity-detail-dialog/activity-detail-dialog';
import { ActivityResource } from '../../activity.resource';

const ACTION_LABELS: Record<AuditLogDto['action'], string> = {
  created: 'Creó',
  updated: 'Editó',
  deleted: 'Eliminó',
};

@Component({
  selector: 'app-activity-page',
  imports: [MatButtonModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './activity.page.html',
  styleUrl: './activity.page.scss',
})
export class ActivityPage {
  private readonly route = inject(ActivatedRoute);
  private readonly activity = inject(ActivityResource);
  private readonly dialog = inject(MatDialog);

  protected readonly userColor = userColor;
  protected readonly userInitial = userInitial;

  // 'activity' tiene path propio, no hereda params del padre por default
  // (paramsInheritanceStrategy: 'emptyOnly') - hay que subir a .parent.
  private readonly workspaceId = Number(
    this.route.snapshot.paramMap.get('workspaceId') ?? this.route.snapshot.parent?.paramMap.get('workspaceId'),
  );

  readonly items = signal<AuditLogDto[]>([]);
  readonly meta = signal<PaginationMeta | null>(null);
  readonly loading = signal(false);

  constructor() {
    this.fetch(1);
  }

  actionLabel(action: AuditLogDto['action']): string {
    return ACTION_LABELS[action];
  }

  formatDate(iso: string): string {
    return new Date(iso).toLocaleString('es-AR');
  }

  openDetail(entry: AuditLogDto): void {
    this.dialog.open<ActivityDetailDialog, ActivityDetailDialogData>(ActivityDetailDialog, {
      data: { entry },
      width: '520px',
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
    this.activity.list(this.workspaceId, { page, per_page: 20 }).subscribe({
      next: ({ items, meta }) => {
        this.loading.set(false);
        this.items.update((current) => (page === 1 ? items : [...current, ...items]));
        this.meta.set(meta);
      },
      error: () => this.loading.set(false),
    });
  }
}
