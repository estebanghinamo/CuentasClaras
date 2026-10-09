import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { ServiceFieldsForm } from '../../../services/components/service-fields-form/service-fields-form';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { LateFeeType } from '../../../../core/models/service.models';
import { SmartSuggestionDto } from '../../../../core/models/suggestion.models';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { AcceptSuggestionResult, SuggestionsResource } from '../../suggestions.resource';

export interface AcceptSuggestionDialogData {
  workspaceId: number;
  suggestion: SmartSuggestionDto;
}

/**
 * Aceptar una sugerencia crea un Service real (POST /suggestions/{id}/accept
 * reusa CreateServiceRequest de M-08 tal cual, ver SmartSuggestionController)
 * - por eso este form comparte los mismos campos que ServiceFormDialog vía
 * ServiceFieldsForm, pero prellenados desde la sugerencia y submiteando a
 * otro endpoint.
 */
@Component({
  selector: 'app-accept-suggestion-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, Spinner, DecimalPipe, ServiceFieldsForm],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './accept-suggestion-dialog.html',
  styleUrl: './accept-suggestion-dialog.scss',
})
export class AcceptSuggestionDialog {
  protected readonly data = inject<AcceptSuggestionDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<AcceptSuggestionDialog, AcceptSuggestionResult | undefined>);
  private readonly fb = inject(FormBuilder);
  private readonly suggestions = inject(SuggestionsResource);

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    name: this.fb.nonNullable.control(this.data.suggestion.description, [Validators.required, Validators.maxLength(150)]),
    amount: this.fb.control<number | null>(this.data.suggestion.avg_amount, [Validators.required, Validators.min(0.01)]),
    is_estimated: this.fb.nonNullable.control(true),
    due_day_start: this.fb.control<number | null>(this.data.suggestion.suggested_due_day, [
      Validators.required, Validators.min(1), Validators.max(31),
    ]),
    due_day_end: this.fb.control<number | null>(null, [Validators.min(1), Validators.max(31)]),
    late_fee_type: this.fb.nonNullable.control<LateFeeType>('fixed'),
    late_fee_value: this.fb.control<number | null>(0, [Validators.required, Validators.min(0)]),
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
    };

    this.suggestions.accept(this.data.workspaceId, this.data.suggestion.id, value).subscribe({
      next: (result) => {
        this.saving.set(false);
        this.dialogRef.close(result);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }
}
