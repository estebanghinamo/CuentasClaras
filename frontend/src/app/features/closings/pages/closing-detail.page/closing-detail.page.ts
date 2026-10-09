import { DatePipe, DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MonthlyClosingDto } from '../../../../core/models/closing.models';
import { SavingsGoalDto } from '../../../../core/models/savings-goal.models';
import { ToastService } from '../../../../core/services/toast.service';
import { AllocateDialog, AllocateDialogData } from '../../components/allocate-dialog/allocate-dialog';
import { ClosingDetailPageData } from '../../closing-detail.resolver';
import { MONTH_LABELS } from '../../../../shared/utils/date.util';

@Component({
  selector: 'app-closing-detail-page',
  imports: [MatButtonModule, DecimalPipe, DatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './closing-detail.page.html',
  styleUrl: './closing-detail.page.scss',
})
export class ClosingDetailPage {
  readonly closingData = input.required<ClosingDetailPageData>();

  private readonly route = inject(ActivatedRoute);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly closing = signal<MonthlyClosingDto | null>(null);
  readonly activeGoals = signal<SavingsGoalDto[]>([]);

  constructor() {
    effect(
      () => {
        const data = this.closingData();
        this.closing.set(data.closing);
        this.activeGoals.set(data.activeGoals);
      },
      { allowSignalWrites: true },
    );
  }

  monthLabel(): string {
    const closing = this.closing();
    return closing ? `${MONTH_LABELS[closing.month - 1]} ${closing.year}` : '';
  }

  openAllocate(): void {
    const closing = this.closing();
    if (!closing) {
      return;
    }

    const ref = this.dialog.open<AllocateDialog, AllocateDialogData, MonthlyClosingDto | undefined>(AllocateDialog, {
      data: { workspaceId: this.workspaceId, closing, goals: this.activeGoals() },
      width: '480px',
    });

    ref.afterClosed().subscribe((updated) => {
      if (updated) {
        this.toast.success('Sobrante asignado.');
        this.closing.set(updated);
      }
    });
  }
}
