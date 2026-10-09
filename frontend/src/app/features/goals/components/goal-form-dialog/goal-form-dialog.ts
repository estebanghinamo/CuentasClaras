import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { ApiError } from '../../../../core/models/api.models';
import { SavingsGoalDto } from '../../../../core/models/savings-goal.models';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { dateToLocalIso, parseLocalIso, tomorrowLocalDate } from '../../../../shared/utils/date.util';
import { GoalsResource } from '../../goals.resource';

export interface GoalFormDialogData {
  workspaceId: number;
  goal: SavingsGoalDto | null;
}

@Component({
  selector: 'app-goal-form-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatDatepickerModule, MatFormFieldModule, MatInputModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './goal-form-dialog.html',
})
export class GoalFormDialog {
  protected readonly data = inject<GoalFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<GoalFormDialog, SavingsGoalDto | undefined>);
  private readonly fb = inject(FormBuilder);
  private readonly goals = inject(GoalsResource);

  protected readonly minDate = tomorrowLocalDate();

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    name: this.fb.nonNullable.control(this.data.goal?.name ?? '', [Validators.required, Validators.maxLength(150)]),
    target_amount: this.fb.control<number | null>(this.data.goal?.target_amount ?? null, [Validators.required, Validators.min(0.01)]),
    due_date: this.fb.control<Date | null>(this.data.goal?.due_date ? parseLocalIso(this.data.goal.due_date) : null),
  });

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }

    this.saving.set(true);
    this.apiError.set(null);
    const value = this.form.getRawValue();
    const request = { name: value.name, target_amount: value.target_amount ?? 0, due_date: value.due_date ? dateToLocalIso(value.due_date) : null };

    const request$ = this.data.goal
      ? this.goals.update(this.data.workspaceId, this.data.goal.id, request)
      : this.goals.create(this.data.workspaceId, request);

    request$.subscribe({
      next: (goal) => {
        this.saving.set(false);
        this.dialogRef.close(goal);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }
}
