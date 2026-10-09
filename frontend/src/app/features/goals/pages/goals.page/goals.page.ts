import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { SavingsGoalDto, SavingsGoalStatus } from '../../../../core/models/savings-goal.models';
import { ToastService } from '../../../../core/services/toast.service';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { SavingsResource } from '../../../savings/savings.resource';
import { ContributeDialog, ContributeDialogData, ContributeMode } from '../../components/contribute-dialog/contribute-dialog';
import { GoalFormDialog, GoalFormDialogData } from '../../components/goal-form-dialog/goal-form-dialog';
import { GoalsPageData } from '../../goals.resolver';
import { GoalsResource } from '../../goals.resource';

const STATUS_LABELS: Record<SavingsGoalStatus, string> = {
  active: 'Activas',
  completed: 'Completadas',
  cancelled: 'Canceladas',
};

@Component({
  selector: 'app-goals-page',
  imports: [MatButtonModule, MatFormFieldModule, MatSelectModule, CurrencyPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './goals.page.html',
  styleUrl: './goals.page.scss',
})
export class GoalsPage {
  readonly goalsData = input.required<GoalsPageData>();

  private readonly route = inject(ActivatedRoute);
  private readonly resource = inject(GoalsResource);
  private readonly savingsResource = inject(SavingsResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly goals = signal<SavingsGoalDto[]>([]);
  readonly walletBalance = signal(0);
  readonly status = signal<SavingsGoalStatus | 'all'>('active');
  readonly statusOptions: (SavingsGoalStatus | 'all')[] = ['active', 'completed', 'cancelled', 'all'];

  constructor() {
    effect(
      () => {
        this.goals.set(this.goalsData().goals);
        this.walletBalance.set(this.goalsData().wallet.balance);
      },
      { allowSignalWrites: true },
    );
  }

  statusLabel(status: SavingsGoalStatus | 'all'): string {
    return status === 'all' ? 'Todas' : STATUS_LABELS[status];
  }

  changeStatus(status: SavingsGoalStatus | 'all'): void {
    this.status.set(status);
    this.reload();
  }

  openForm(goal: SavingsGoalDto | null): void {
    const ref = this.dialog.open<GoalFormDialog, GoalFormDialogData, SavingsGoalDto | undefined>(GoalFormDialog, {
      data: { workspaceId: this.workspaceId, goal },
      width: '460px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(goal ? 'Meta actualizada.' : 'Meta creada.');
        this.reload();
      }
    });
  }

  openContribute(goal: SavingsGoalDto, mode: ContributeMode): void {
    const ref = this.dialog.open<ContributeDialog, ContributeDialogData, boolean | undefined>(ContributeDialog, {
      data: { workspaceId: this.workspaceId, goal, mode, walletBalance: this.walletBalance() },
      width: '420px',
    });

    ref.afterClosed().subscribe((changed) => {
      if (changed) {
        this.toast.success('Meta actualizada.');
        this.reload();
        if (mode === 'transfer') {
          this.savingsResource.getWallet(this.workspaceId).subscribe((wallet) => this.walletBalance.set(wallet.balance));
        }
      }
    });
  }

  cancelGoal(goal: SavingsGoalDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Cancelar meta',
          message: `¿Cancelar la meta "${goal.name}"?`,
          confirmLabel: 'Cancelar meta',
          cancelLabel: 'Volver',
          icon: 'flag',
          tone: 'danger',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }

        this.resource.cancel(this.workspaceId, goal.id).subscribe({
          next: () => {
            this.toast.success('Meta cancelada.');
            this.reload();
          },
        });
      });
  }

  private reload(): void {
    this.resource.list(this.workspaceId, this.status()).subscribe((goals) => this.goals.set(goals));
  }
}
