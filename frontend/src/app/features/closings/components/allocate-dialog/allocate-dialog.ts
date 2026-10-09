import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { ApiError } from '../../../../core/models/api.models';
import { AllocateClosingRequest, MonthlyClosingDto } from '../../../../core/models/closing.models';
import { SavingsGoalDto } from '../../../../core/models/savings-goal.models';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { fieldError, formErrorMessage } from '../../../../shared/utils/api-error.util';
import { MONTH_LABELS } from '../../../../shared/utils/date.util';
import { ClosingsResource } from '../../closings.resource';

export interface AllocateDialogData {
  workspaceId: number;
  closing: MonthlyClosingDto;
  /** Metas activas del workspace (ver ClosingDetailPageData) - solo se puede asignar a metas activas. */
  goals: SavingsGoalDto[];
}

/**
 * Reparte unallocated_amount entre el monedero y metas (M-13 §4 allocate).
 * Se puede asignar en varias veces hasta agotar el sobrante - por eso el
 * form arranca en 0 y valida en vivo contra unallocated_amount, no contra
 * remaining_amount.
 */
@Component({
  selector: 'app-allocate-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatFormFieldModule, MatInputModule, Spinner, DecimalPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './allocate-dialog.html',
  styleUrl: './allocate-dialog.scss',
})
export class AllocateDialog {
  protected readonly data = inject<AllocateDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<AllocateDialog, MonthlyClosingDto | undefined>);
  private readonly fb = inject(FormBuilder);
  private readonly resource = inject(ClosingsResource);

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);
  readonly formError = () => formErrorMessage(this.apiError());

  readonly nextMonthOpen = this.data.closing.next_month_open;
  readonly nextMonthLabel = (() => {
    const nextMonth = this.data.closing.month === 12 ? 1 : this.data.closing.month + 1;
    const nextYear = this.data.closing.month === 12 ? this.data.closing.year + 1 : this.data.closing.year;
    return `${MONTH_LABELS[nextMonth - 1]} ${nextYear}`;
  })();

  readonly form = this.fb.group({
    to_wallet: this.fb.nonNullable.control(0, [Validators.required, Validators.min(0)]),
    goals: this.fb.array(this.data.goals.map(() => this.fb.nonNullable.control(0, [Validators.min(0)]))),
    to_next_month: this.fb.nonNullable.control({ value: 0, disabled: !this.nextMonthOpen }, [Validators.min(0)]),
  });

  totalRequested(): number {
    const raw = this.form.getRawValue();
    return (
      (raw.to_wallet ?? 0) +
      raw.goals.reduce((sum: number, amount: number) => sum + (amount || 0), 0) +
      (this.nextMonthOpen ? raw.to_next_month ?? 0 : 0)
    );
  }

  exceedsAvailable(): boolean {
    return this.totalRequested() > this.data.closing.unallocated_amount;
  }

  nothingSelected(): boolean {
    return this.totalRequested() <= 0;
  }

  submit(): void {
    if (this.form.invalid || this.saving() || this.exceedsAvailable() || this.nothingSelected()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    const raw = this.form.getRawValue();
    const request: AllocateClosingRequest = {
      to_wallet: raw.to_wallet ?? 0,
      to_goals: this.data.goals
        .map((goal, index) => ({ goal_id: goal.id, amount: raw.goals[index] ?? 0 }))
        .filter((entry) => entry.amount > 0),
      to_next_month: this.nextMonthOpen ? raw.to_next_month ?? 0 : 0,
    };

    this.resource.allocate(this.data.workspaceId, this.data.closing.year, this.data.closing.month, request).subscribe({
      next: (closing) => {
        this.saving.set(false);
        this.dialogRef.close(closing);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }
}
