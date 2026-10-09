import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { ServiceFieldsForm } from '../service-fields-form/service-fields-form';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { LateFeeType, ServiceDto } from '../../../../core/models/service.models';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { ServicesResource } from '../../services.resource';

export interface ServiceFormDialogData {
  workspaceId: number;
  service: ServiceDto | null;
}

@Component({
  selector: 'app-service-form-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatCheckboxModule, Spinner, ServiceFieldsForm],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './service-form-dialog.html',
  styleUrl: './service-form-dialog.scss',
})
export class ServiceFormDialog {
  protected readonly data = inject<ServiceFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<ServiceFormDialog>);
  private readonly fb = inject(FormBuilder);
  private readonly services = inject(ServicesResource);

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    name: this.fb.nonNullable.control(this.data.service?.name ?? '', [Validators.required, Validators.maxLength(150)]),
    amount: this.fb.control<number | null>(this.data.service?.amount ?? null, [Validators.required, Validators.min(0.01)]),
    is_estimated: this.fb.nonNullable.control(this.data.service?.is_estimated ?? false),
    due_day_start: this.fb.control<number | null>(this.data.service?.due_day_start ?? null, [
      Validators.required, Validators.min(1), Validators.max(31),
    ]),
    due_day_end: this.fb.control<number | null>(this.data.service?.due_day_end ?? null, [Validators.min(1), Validators.max(31)]),
    late_fee_type: this.fb.nonNullable.control<LateFeeType>(this.data.service?.late_fee_type ?? 'fixed'),
    late_fee_value: this.fb.control<number | null>(this.data.service?.late_fee_value ?? 0, [Validators.required, Validators.min(0)]),
    active: this.fb.nonNullable.control(this.data.service?.active ?? true),
  });

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    const raw = this.form.getRawValue();
    const value = {
      name: raw.name,
      amount: raw.amount ?? 0,
      is_estimated: raw.is_estimated,
      due_day_start: raw.due_day_start ?? 1,
      due_day_end: raw.due_day_end || null,
      late_fee_type: raw.late_fee_type,
      late_fee_value: raw.late_fee_value ?? 0,
      active: raw.active,
    };

    const request$ = this.data.service
      ? this.services.update(this.data.workspaceId, this.data.service.id, value)
      : this.services.create(this.data.workspaceId, value);

    request$.subscribe({
      next: (service) => {
        this.saving.set(false);
        this.dialogRef.close(service);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }
}
