import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { SettlementSummaryDto, SettlementTransferDto } from '../../../../core/models/settlement.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { ToastService } from '../../../../core/services/toast.service';
import {
  SettlementPaymentDialog,
  SettlementPaymentDialogData,
} from '../../components/settlement-payment-dialog/settlement-payment-dialog';
import { SettlementResource } from '../../settlement.resource';

@Component({
  selector: 'app-settlement-page',
  imports: [MatButtonModule, DecimalPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './settlement.page.html',
  styleUrl: './settlement.page.scss',
})
export class SettlementPage {
  readonly settlementData = input.required<SettlementSummaryDto>();

  private readonly route = inject(ActivatedRoute);
  private readonly resource = inject(SettlementResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly summary = signal<SettlementSummaryDto | null>(null);
  readonly members = computed(() => this.summary()?.balances.map((b) => ({ user_id: b.user_id, user_name: b.user_name })) ?? []);

  constructor() {
    effect(() => this.summary.set(this.settlementData()), { allowSignalWrites: true });
  }

  balanceTone(balance: number): 'positive' | 'negative' | 'neutral' {
    if (balance > 0) return 'positive';
    if (balance < 0) return 'negative';
    return 'neutral';
  }

  openFreePayment(): void {
    this.openPaymentDialog({});
  }

  openMarkAsPaid(transfer: SettlementTransferDto): void {
    this.openPaymentDialog({ fromUserId: transfer.from_user_id, toUserId: transfer.to_user_id, amount: transfer.amount });
  }

  private openPaymentDialog(prefill: { fromUserId?: number; toUserId?: number; amount?: number }): void {
    const ref = this.dialog.open<SettlementPaymentDialog, SettlementPaymentDialogData>(SettlementPaymentDialog, {
      data: { workspaceId: this.workspaceId, members: this.members(), ...prefill },
      width: '420px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success('Pago registrado.');
        this.reload();
      }
    });
  }

  undoPayment(paymentId: number): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Deshacer pago',
          message: '¿Deshacer este pago? Va a volver a aparecer como liquidación pendiente.',
          confirmLabel: 'Deshacer',
          cancelLabel: 'Volver',
          icon: 'undo',
          tone: 'danger',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }

        this.resource.deletePayment(this.workspaceId, paymentId).subscribe({
          next: () => {
            this.toast.success('Pago deshecho.');
            this.reload();
          },
        });
      });
  }

  private reload(): void {
    this.resource.getSummary(this.workspaceId).subscribe((summary) => this.summary.set(summary));
  }
}
