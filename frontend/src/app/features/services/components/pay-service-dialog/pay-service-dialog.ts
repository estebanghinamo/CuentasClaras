import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { LateFeeType, ServicePaymentDto } from '../../../../core/models/service.models';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { todayLocalIso } from '../../../../shared/utils/date.util';
import { ServicePaymentsResource } from '../../service-payments.resource';

export interface PayServiceDialogData {
  workspaceId: number;
  payment: ServicePaymentDto;
  lateFeeType: LateFeeType;
  lateFeeValue: number;
}

@Component({
  selector: 'app-pay-service-dialog',
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatCheckboxModule,
    MatFormFieldModule,
    MatInputModule,
    Spinner,
    DecimalPipe,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './pay-service-dialog.html',
})
export class PayServiceDialog {
  protected readonly data = inject<PayServiceDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<PayServiceDialog>);
  private readonly fb = inject(FormBuilder);
  private readonly servicePayments = inject(ServicePaymentsResource);

  protected readonly today = todayLocalIso();

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    amount_paid: this.fb.control<number | null>(this.data.payment.expected_amount, [Validators.required, Validators.min(0.01)]),
    paid_at: this.fb.nonNullable.control(this.today, [Validators.required]),
    apply_late_fee: this.fb.nonNullable.control(true),
    fee_difference: this.fb.control<number | null>(null),
  });

  private readonly formValue = toSignal(this.form.valueChanges, { initialValue: this.form.getRawValue() });

  readonly isLate = () => (this.formValue().paid_at ?? this.today) > this.data.payment.due_date_end;

  /** Recargo calculado según la config del servicio, sin importar si el checkbox lo incluye o no (para mostrarlo en la etiqueta). */
  readonly calculatedFee = (): number => {
    if (!this.isLate()) {
      return 0;
    }
    const base = this.formValue().amount_paid ?? this.data.payment.expected_amount;

    return this.data.lateFeeType === 'percentage' ? Math.round(base * this.data.lateFeeValue) / 100 : this.data.lateFeeValue;
  };

  readonly totalLateFee = (): number => {
    const applied = this.formValue().apply_late_fee ? this.calculatedFee() : 0;

    return applied + (this.formValue().fee_difference ?? 0);
  };

  readonly totalAmount = (): number => (this.formValue().amount_paid ?? this.data.payment.expected_amount) + this.totalLateFee();

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    const raw = this.form.getRawValue();

    this.servicePayments.pay(this.data.workspaceId, this.data.payment.id, {
      amount_paid: raw.amount_paid,
      paid_at: raw.paid_at,
      apply_late_fee: raw.apply_late_fee,
      fee_difference: raw.fee_difference,
    }).subscribe({
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
