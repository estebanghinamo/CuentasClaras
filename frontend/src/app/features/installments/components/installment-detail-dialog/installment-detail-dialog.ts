import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { InstallmentDto, InstallmentPaymentDto } from '../../../../core/models/installment.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { todayLocalIso } from '../../../../shared/utils/date.util';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { InstallmentsResource } from '../../installments.resource';

export interface InstallmentDetailDialogData {
  workspaceId: number;
  installment: InstallmentDto;
}

const MONTH_LABELS = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

@Component({
  selector: 'app-installment-detail-dialog',
  imports: [MatDialogModule, MatButtonModule, CurrencyPipe, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './installment-detail-dialog.html',
  styleUrl: './installment-detail-dialog.scss',
})
export class InstallmentDetailDialog {
  protected readonly data = inject<InstallmentDetailDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<InstallmentDetailDialog>);
  private readonly resource = inject(InstallmentsResource);
  private readonly dialog = inject(MatDialog);
  protected readonly context = inject(WorkspaceContextService);

  // El backend no garantiza el orden - se ordena acá por número de cuota
  // para que la 1 aparezca arriba, la 2 abajo, etc. (a pedido del usuario).
  readonly payments = signal<InstallmentPaymentDto[]>(
    [...(this.data.installment.payments ?? [])].sort((a, b) => a.number - b.number),
  );
  readonly saving = signal(false);

  progress(): number {
    return Math.round((this.data.installment.paid_count / this.data.installment.installments_count) * 100);
  }

  monthLabel(month: number): string {
    return MONTH_LABELS[month];
  }

  firstUnpaidNumber(): number | null {
    const pending = this.payments().filter((p) => p.status === 'pending');
    return pending.length === 0 ? null : Math.min(...pending.map((p) => p.number));
  }

  canPay(payment: InstallmentPaymentDto): boolean {
    return payment.number === this.firstUnpaidNumber();
  }

  pay(payment: InstallmentPaymentDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Confirmar pago',
          message: `¿Confirmás pagar la cuota ${payment.number} (${this.monthLabel(payment.month)} ${payment.year})?`,
          confirmLabel: 'Pagar',
          cancelLabel: 'Cancelar',
          icon: 'credit_card',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }

        this.saving.set(true);
        this.resource.pay(this.data.workspaceId, this.data.installment.id, payment.id, todayLocalIso()).subscribe({
          next: () => this.dialogRef.close(true),
          error: () => this.saving.set(false),
        });
      });
  }

  unpay(payment: InstallmentPaymentDto): void {
    this.saving.set(true);
    this.resource.unpay(this.data.workspaceId, this.data.installment.id, payment.id).subscribe({
      next: () => this.dialogRef.close(true),
      error: () => this.saving.set(false),
    });
  }
}
