import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { ApiError } from '../../../../core/models/api.models';
import { SettlementPaymentDto } from '../../../../core/models/settlement.models';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { SettlementResource } from '../../settlement.resource';

export interface SettlementPaymentDialogMember {
  user_id: number;
  user_name: string;
}

export interface SettlementPaymentDialogData {
  workspaceId: number;
  members: SettlementPaymentDialogMember[];
  fromUserId?: number;
  toUserId?: number;
  amount?: number;
}

@Component({
  selector: 'app-settlement-payment-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatFormFieldModule, MatInputModule, MatSelectModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './settlement-payment-dialog.html',
})
export class SettlementPaymentDialog {
  protected readonly data = inject<SettlementPaymentDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<SettlementPaymentDialog, SettlementPaymentDto | undefined>);
  private readonly fb = inject(FormBuilder);
  private readonly settlements = inject(SettlementResource);

  protected readonly members = this.data.members;

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    from_user_id: this.fb.control<number | null>(this.data.fromUserId ?? null, [Validators.required]),
    to_user_id: this.fb.control<number | null>(this.data.toUserId ?? null, [Validators.required]),
    amount: this.fb.control<number | null>(this.data.amount ?? null, [Validators.required, Validators.min(0.01)]),
    note: this.fb.control<string | null>(null, [Validators.maxLength(255)]),
  });

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }
    const value = this.form.getRawValue();
    if (value.from_user_id === value.to_user_id) {
      this.form.controls.to_user_id.setErrors({ sameAsFrom: true });
      return;
    }

    this.saving.set(true);
    this.apiError.set(null);

    this.settlements
      .createPayment(this.data.workspaceId, {
        from_user_id: value.from_user_id!,
        to_user_id: value.to_user_id!,
        amount: value.amount ?? 0,
        note: value.note || null,
      })
      .subscribe({
        next: (payment) => {
          this.saving.set(false);
          this.dialogRef.close(payment);
        },
        error: (error: ApiError) => {
          this.saving.set(false);
          this.apiError.set(error);
        },
      });
  }
}
