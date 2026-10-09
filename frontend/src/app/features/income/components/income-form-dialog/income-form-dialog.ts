import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { IncomeEntryDto } from '../../../../core/models/income.models';
import { IncomeResource } from '../../income.resource';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { todayLocalIso } from '../../../../shared/utils/date.util';

export interface IncomeFormDialogData {
  workspaceId: number;
  entry: IncomeEntryDto | null;
}

const CONCEPT_SUGGESTIONS = ['Sueldo', 'Aguinaldo', 'Freelance', 'Extra'];

@Component({
  selector: 'app-income-form-dialog',
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatAutocompleteModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    Spinner,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './income-form-dialog.html',
})
export class IncomeFormDialog {
  protected readonly data = inject<IncomeFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<IncomeFormDialog>);
  private readonly fb = inject(FormBuilder);
  private readonly income = inject(IncomeResource);

  protected readonly suggestions = CONCEPT_SUGGESTIONS;
  protected readonly currentMonthStart = `${todayLocalIso().slice(0, 7)}-01`;
  protected readonly currentMonthEnd = this.lastDayOfCurrentMonth();

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    amount: this.fb.control<number | null>(this.data.entry?.amount ?? null, [Validators.required, Validators.min(0.01)]),
    date: this.fb.nonNullable.control(this.data.entry?.date ?? todayLocalIso(), [Validators.required]),
    concept: this.fb.nonNullable.control(this.data.entry?.concept ?? '', [Validators.required, Validators.maxLength(150)]),
  });

  // Igual que en Gastos: solo sugiere cuando lo tipeado se parece a un
  // concepto conocido, en vez de mostrar todas las sugerencias sueltas abajo.
  private readonly conceptValue = toSignal(this.form.controls.concept.valueChanges, {
    initialValue: this.form.controls.concept.value,
  });

  readonly filteredSuggestions = computed(() => {
    const term = this.conceptValue().trim().toLowerCase();
    if (!term) {
      return this.suggestions;
    }
    return this.suggestions.filter((s) => s.toLowerCase().includes(term) && s.toLowerCase() !== term);
  });

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    const value = { ...this.form.getRawValue(), amount: this.form.getRawValue().amount ?? 0 };

    const request$ = this.data.entry
      ? this.income.update(this.data.workspaceId, this.data.entry.id, value)
      : this.income.create(this.data.workspaceId, value);

    request$.subscribe({
      next: (entry) => {
        this.saving.set(false);
        this.dialogRef.close(entry);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }

  private lastDayOfCurrentMonth(): string {
    const [year, month] = this.currentMonthStart.split('-').map(Number);
    const lastDay = new Date(year, month, 0).getDate();

    return `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
  }
}
