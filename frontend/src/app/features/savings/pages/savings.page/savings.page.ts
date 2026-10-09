import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { PaginationMeta } from '../../../../core/models/api.models';
import { SavingsMovementDto } from '../../../../core/models/savings.models';
import { LineChart } from '../../../../shared/components/line-chart/line-chart';
import { ToastService } from '../../../../core/services/toast.service';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { MovementFormDialog, MovementFormDialogData } from '../../components/movement-form-dialog/movement-form-dialog';
import { SavingsPageData } from '../../savings.resolver';
import { SavingsResource } from '../../savings.resource';

const MONTH_LABELS = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

@Component({
  selector: 'app-savings-page',
  imports: [MatButtonModule, CurrencyPipe, LineChart],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './savings.page.html',
  styleUrl: './savings.page.scss',
})
export class SavingsPage {
  readonly savingsData = input.required<SavingsPageData>();

  private readonly route = inject(ActivatedRoute);
  private readonly resource = inject(SavingsResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly wallet = signal<SavingsPageData['wallet'] | null>(null);
  readonly movements = signal<SavingsMovementDto[]>([]);
  readonly meta = signal<PaginationMeta | null>(null);
  readonly history = signal<SavingsPageData['history']>([]);

  readonly historyLabels = computed(() => this.history().map((point) => `${MONTH_LABELS[point.month]} ${String(point.year).slice(2)}`));
  readonly historyData = computed(() => this.history().map((point) => point.balance_end));

  private page = 1;

  constructor() {
    effect(
      () => {
        const data = this.savingsData();
        this.wallet.set(data.wallet);
        this.movements.set(data.movements.items);
        this.meta.set(data.movements.meta);
        this.history.set(data.history);
      },
      { allowSignalWrites: true },
    );
  }

  monthLabel(month: number): string {
    return MONTH_LABELS[month];
  }

  changePage(delta: number): void {
    this.page += delta;
    this.reloadMovements();
  }

  openMovement(type: 'deposit' | 'withdraw'): void {
    const ref = this.dialog.open<MovementFormDialog, MovementFormDialogData, SavingsMovementDto | undefined>(MovementFormDialog, {
      data: { workspaceId: this.workspaceId, type, currentBalance: this.wallet()?.balance ?? 0 },
      width: '420px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(type === 'deposit' ? 'Depósito registrado.' : 'Retiro registrado.');
        this.page = 1;
        this.reloadAll();
      }
    });
  }

  private reloadMovements(): void {
    this.resource.listMovements(this.workspaceId, this.page, 20).subscribe((result) => {
      this.movements.set(result.items);
      this.meta.set(result.meta);
    });
  }

  private reloadAll(): void {
    this.resource.getWallet(this.workspaceId).subscribe((wallet) => this.wallet.set(wallet));
    this.resource.getHistory(this.workspaceId, 12).subscribe((history) => this.history.set(history));
    this.reloadMovements();
  }
}
