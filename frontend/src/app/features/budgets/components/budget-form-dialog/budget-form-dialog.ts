import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { ApiError } from '../../../../core/models/api.models';
import { BudgetDto } from '../../../../core/models/budget.models';
import { CategoryDto } from '../../../../core/models/category.models';
import { CategoriesService } from '../../../categories/categories.service';
import { CategoryFormDialog, CategoryFormDialogData } from '../../../categories/components/category-form-dialog/category-form-dialog';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { BudgetsResource } from '../../budgets.resource';

export interface BudgetFormDialogData {
  workspaceId: number;
  year: number;
  month: number;
  budget: BudgetDto | null;
  categories: CategoryDto[];
}

@Component({
  selector: 'app-budget-form-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatFormFieldModule, MatInputModule, MatSelectModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './budget-form-dialog.html',
  styleUrl: './budget-form-dialog.scss',
})
export class BudgetFormDialog {
  protected readonly data = inject<BudgetFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<BudgetFormDialog, BudgetDto | undefined>);
  private readonly fb = inject(FormBuilder);
  private readonly budgets = inject(BudgetsResource);
  private readonly dialog = inject(MatDialog);
  private readonly categoriesService = inject(CategoriesService);

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly categories = signal<CategoryDto[]>(this.data.categories);

  readonly form = this.fb.group({
    category_id: this.fb.control<number | null>(this.data.budget?.category_id ?? null, [Validators.required]),
    limit_amount: this.fb.control<number | null>(this.data.budget?.limit_amount ?? null, [Validators.required, Validators.min(0.01)]),
  });

  openCategoryForm(): void {
    const ref = this.dialog.open<CategoryFormDialog, CategoryFormDialogData, CategoryDto | undefined>(CategoryFormDialog, {
      data: { workspaceId: this.data.workspaceId, category: null },
      width: '480px',
    });

    ref.afterClosed().subscribe((category) => {
      if (category) {
        this.categoriesService.invalidate(this.data.workspaceId);
        this.categories.update((items) => [...items, category]);
        this.form.controls.category_id.setValue(category.id);
      }
    });
  }

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }

    this.saving.set(true);
    this.apiError.set(null);
    const value = this.form.getRawValue();

    this.budgets
      .upsert(this.data.workspaceId, {
        category_id: this.data.budget?.category_id ?? (value.category_id as number),
        year: this.data.year,
        month: this.data.month,
        limit_amount: value.limit_amount ?? 0,
      })
      .subscribe({
        next: (budget) => {
          this.saving.set(false);
          this.dialogRef.close(budget);
        },
        error: (error: ApiError) => {
          this.saving.set(false);
          this.apiError.set(error);
        },
      });
  }
}
