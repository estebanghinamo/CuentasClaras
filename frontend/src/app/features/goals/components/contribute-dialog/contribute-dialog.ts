import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { map } from 'rxjs';
import { ApiError } from '../../../../core/models/api.models';
import { SavingsGoalDto } from '../../../../core/models/savings-goal.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { DashboardResource } from '../../../dashboard/dashboard.resource';
import { GoalsResource } from '../../goals.resource';

export type ContributeMode = 'contribution' | 'withdrawal' | 'transfer';

export interface ContributeDialogData {
  workspaceId: number;
  goal: SavingsGoalDto;
  mode: ContributeMode;
  walletBalance: number;
}

const TITLES: Record<ContributeMode, string> = {
  contribution: 'Aportar a la meta',
  withdrawal: 'Retirar de la meta',
  transfer: 'Transferir del monedero',
};

@Component({
  selector: 'app-contribute-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatFormFieldModule, MatInputModule, Spinner, CurrencyPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './contribute-dialog.html',
})
export class ContributeDialog {
  protected readonly data = inject<ContributeDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<ContributeDialog, boolean | undefined>);
  private readonly fb = inject(FormBuilder);
  private readonly goals = inject(GoalsResource);
  private readonly dialog = inject(MatDialog);
  private readonly dashboardResource = inject(DashboardResource);
  protected readonly context = inject(WorkspaceContextService);

  protected readonly title = TITLES[this.data.mode];

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly maxAmount = signal<number | null>(this.computeMaxAmount());

  readonly form = this.fb.group({
    amount: this.fb.control<number | null>(
      null,
      [Validators.required, Validators.min(0.01)].concat(
        this.maxAmount() !== null ? [Validators.max(this.maxAmount()!)] : [],
      ),
    ),
    note: this.fb.control<string | null>(null, [Validators.maxLength(255)]),
  });

  constructor() {
    if (this.data.mode === 'contribution') {
      this.dashboardResource.get(this.data.workspaceId).subscribe((dashboard) => {
        const available = Math.max(0, dashboard.totals.available);
        this.maxAmount.set(available);
        this.form.controls.amount.addValidators(Validators.max(available));
        this.form.controls.amount.updateValueAndValidity();
      });
    }
  }

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }

    if (this.data.mode === 'contribution') {
      const amount = this.form.getRawValue().amount ?? 0;

      this.dialog
        .open(ConfirmDialog, {
          width: '420px',
          data: {
            title: 'Confirmar aporte',
            message: `Vas a aportar ${amount.toFixed(2)} a "${this.data.goal.name}". Este monto se descuenta de tu "Disponible" del mes en el Dashboard.`,
            confirmLabel: 'Aportar',
            cancelLabel: 'Cancelar',
            icon: 'savings',
          },
        })
        .afterClosed()
        .subscribe((confirmed) => {
          if (confirmed) {
            this.doSubmit();
          }
        });

      return;
    }

    this.doSubmit();
  }

  private doSubmit(): void {
    this.saving.set(true);
    this.apiError.set(null);
    const value = this.form.getRawValue();
    const amount = value.amount ?? 0;
    const note = value.note || null;

    const request$ =
      this.data.mode === 'transfer'
        ? this.goals.transferFromWallet(this.data.workspaceId, this.data.goal.id, { amount, note }).pipe(map(() => undefined))
        : this.goals
            .contribute(this.data.workspaceId, this.data.goal.id, { type: this.data.mode, amount, note })
            .pipe(map(() => undefined));

    request$.subscribe({
      next: () => {
        this.saving.set(false);
        this.dialogRef.close(true);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }

  private computeMaxAmount(): number | null {
    if (this.data.mode === 'withdrawal') {
      return this.data.goal.current_amount;
    }
    if (this.data.mode === 'transfer') {
      return this.data.walletBalance;
    }

    return null;
  }
}
