import { CurrencyPipe, DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { ExpenseDto, PAYMENT_METHOD_LABELS } from '../../../../core/models/expense.models';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';

export interface ExpenseDetailsDialogData {
  expense: ExpenseDto;
}

@Component({
  selector: 'app-expense-details-dialog',
  imports: [MatDialogModule, MatButtonModule, CurrencyPipe, DatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './expense-details-dialog.html',
  styleUrl: './expense-details-dialog.scss',
})
export class ExpenseDetailsDialog {
  protected readonly data = inject<ExpenseDetailsDialogData>(MAT_DIALOG_DATA);
  protected readonly context = inject(WorkspaceContextService);

  paymentMethodLabel(): string {
    return PAYMENT_METHOD_LABELS[this.data.expense.payment_method];
  }
}
