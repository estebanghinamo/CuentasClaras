import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { ApiError } from '../../../../core/models/api.models';
import { DashboardResource } from '../../../dashboard/dashboard.resource';
import { SavingsMovementDto, SavingsMovementType } from '../../../../core/models/savings.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { SavingsResource } from '../../savings.resource';

export interface MovementFormDialogData {
  workspaceId: number;
  type: SavingsMovementType;
  currentBalance: number;
}

@Component({
  selector: 'app-movement-form-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatFormFieldModule, MatInputModule, Spinner, CurrencyPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './movement-form-dialog.html',
})
export class MovementFormDialog {
  protected readonly data = inject<MovementFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<MovementFormDialog, SavingsMovementDto | undefined>);
  private readonly fb = inject(FormBuilder);
  private readonly savings = inject(SavingsResource);
  private readonly dialog = inject(MatDialog);
  private readonly dashboardResource = inject(DashboardResource);
  protected readonly context = inject(WorkspaceContextService);

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly available = signal<number | null>(this.data.type === 'withdraw' ? this.data.currentBalance : null);

  readonly form = this.fb.group({
    amount: this.fb.control<number | null>(
      null,
      [Validators.required, Validators.min(0.01)].concat(
        this.data.type === 'withdraw' ? [Validators.max(this.data.currentBalance)] : [],
      ),
    ),
    note: this.fb.control<string | null>(null, [Validators.maxLength(255)]),
  });

  constructor() {
    if (this.data.type === 'deposit') {
      this.dashboardResource.get(this.data.workspaceId).subscribe((dashboard) => {
        const available = Math.max(0, dashboard.totals.available);
        this.available.set(available);
        this.form.controls.amount.addValidators(Validators.max(available));
        this.form.controls.amount.updateValueAndValidity();
      });
    }
  }

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }

    if (this.data.type === 'deposit') {
      const amount = this.form.getRawValue().amount ?? 0;

      this.dialog
        .open(ConfirmDialog, {
          width: '420px',
          data: {
            title: 'Confirmar depósito',
            message: `Vas a depositar ${amount.toFixed(2)} al monedero. Este monto se descuenta de tu "Disponible" del mes en el Dashboard.`,
            confirmLabel: 'Depositar',
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

    this.savings
      .createMovement(this.data.workspaceId, {
        type: this.data.type,
        amount: value.amount ?? 0,
        note: value.note || null,
      })
      .subscribe({
        next: (movement) => {
          this.saving.set(false);
          this.dialogRef.close(movement);
        },
        error: (error: ApiError) => {
          // El modal de error ya lo muestra globalErrorInterceptor para
          // cualquier endpoint - acá solo guardamos el error para pintar el
          // campo puntual (serverFieldError) si vino un 422 de validación.
          this.saving.set(false);
          this.apiError.set(error);
        },
      });
  }
}
