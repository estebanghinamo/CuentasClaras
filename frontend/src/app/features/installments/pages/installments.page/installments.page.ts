import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { InstallmentDto, InstallmentStatus } from '../../../../core/models/installment.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { ToastService } from '../../../../core/services/toast.service';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { InstallmentDetailDialog, InstallmentDetailDialogData } from '../../components/installment-detail-dialog/installment-detail-dialog';
import { InstallmentFormDialog, InstallmentFormDialogData } from '../../components/installment-form-dialog/installment-form-dialog';
import { InstallmentsPageData } from '../../installments.resolver';
import { InstallmentsResource } from '../../installments.resource';

const STATUS_LABELS: Record<InstallmentStatus, string> = {
  active: 'Activas',
  completed: 'Completadas',
  cancelled: 'Canceladas',
};

type InstallmentStatusFilter = InstallmentStatus | 'all';

@Component({
  selector: 'app-installments-page',
  imports: [MatButtonModule, MatFormFieldModule, MatSelectModule, CurrencyPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './installments.page.html',
  styleUrl: './installments.page.scss',
})
export class InstallmentsPage {
  readonly installmentsData = input.required<InstallmentsPageData>();
  private readonly route = inject(ActivatedRoute);
  private readonly resource = inject(InstallmentsResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);
  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly items = signal<InstallmentDto[]>([]);
  readonly status = signal<InstallmentStatusFilter>('all');
  readonly statusOptions: InstallmentStatusFilter[] = ['active', 'completed', 'cancelled', 'all'];

  constructor() {
    effect(() => this.items.set(this.installmentsData().installments), { allowSignalWrites: true });
  }

  statusLabel(status: InstallmentStatusFilter): string {
    return status === 'all' ? 'Todas' : STATUS_LABELS[status];
  }

  progress(item: InstallmentDto): number {
    return Math.round((item.paid_count / item.installments_count) * 100);
  }

  changeStatus(status: InstallmentStatusFilter): void {
    this.status.set(status);
    this.reload();
  }

  openForm(installment: InstallmentDto | null): void {
    const ref = this.dialog.open<InstallmentFormDialog, InstallmentFormDialogData, InstallmentDto | undefined>(InstallmentFormDialog, {
      data: { workspaceId: this.workspaceId, installment, categories: this.installmentsData().categories },
      width: '480px',
    });
    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(installment ? 'Compra actualizada.' : 'Compra creada.');
        this.reload();
      }
    });
  }

  installmentRowKey(item: InstallmentDto): string {
    return String(item.id);
  }

  openDetail(installment: InstallmentDto): void {
    this.resource.get(this.workspaceId, installment.id).subscribe((detail) => {
      const ref = this.dialog.open<InstallmentDetailDialog, InstallmentDetailDialogData, boolean>(InstallmentDetailDialog, {
        data: { workspaceId: this.workspaceId, installment: detail },
        width: '560px',
      });
      ref.afterClosed().subscribe((changed) => {
        if (changed) {
          this.toast.success('Cuota actualizada.');
          this.reload();
        }
      });
    });
  }

  delete(installment: InstallmentDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Cancelar compra',
          message: `¿Cancelar la compra "${installment.description}"? Las cuotas ya pagadas se conservarán.`,
          confirmLabel: 'Cancelar compra',
          cancelLabel: 'Volver',
          icon: 'delete',
          tone: 'danger',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.resource.delete(this.workspaceId, installment.id).subscribe({
          next: () => {
            this.toast.success('Compra cancelada.');
            this.reload();
          },
        });
      });
  }

  private reload(): void {
    this.resource.list(this.workspaceId, this.status()).subscribe((items) => this.items.set(items));
  }
}
