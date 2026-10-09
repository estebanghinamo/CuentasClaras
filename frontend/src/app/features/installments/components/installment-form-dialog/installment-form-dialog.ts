import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { ApiError } from '../../../../core/models/api.models';
import { CategoryDto } from '../../../../core/models/category.models';
import { CategoriesService } from '../../../categories/categories.service';
import { CategoryFormDialog, CategoryFormDialogData } from '../../../categories/components/category-form-dialog/category-form-dialog';
import { InstallmentDto } from '../../../../core/models/installment.models';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { todayLocalIso } from '../../../../shared/utils/date.util';
import { InstallmentsResource } from '../../installments.resource';

export interface InstallmentFormDialogData {
  workspaceId: number;
  installment: InstallmentDto | null;
  categories: CategoryDto[];
}

@Component({
  selector: 'app-installment-form-dialog',
  imports: [ReactiveFormsModule, MatDialogModule, MatButtonModule, MatFormFieldModule, MatInputModule, MatSelectModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './installment-form-dialog.html',
  styleUrl: './installment-form-dialog.scss',
})
export class InstallmentFormDialog {
  protected readonly data = inject<InstallmentFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<InstallmentFormDialog>);
  private readonly fb = inject(FormBuilder);
  private readonly installments = inject(InstallmentsResource);
  private readonly dialog = inject(MatDialog);
  private readonly categoriesService = inject(CategoriesService);

  protected readonly minStartDate = `${todayLocalIso().slice(0, 7)}-01`;
  protected readonly maxStartDate = this.addMonths(this.minStartDate, 12);
  readonly saving = signal(false);
  readonly categories = signal<CategoryDto[]>(this.data.categories);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    description: this.fb.nonNullable.control(this.data.installment?.description ?? '', [Validators.required, Validators.maxLength(255)]),
    total_amount: this.fb.control<number | null>(this.data.installment?.total_amount ?? null, [Validators.required, Validators.min(0.01)]),
    installments_count: this.fb.control<number | null>(this.data.installment?.installments_count ?? null, [Validators.required, Validators.min(1), Validators.max(120)]),
    start_date: this.fb.nonNullable.control(this.data.installment?.start_date ?? this.minStartDate, [Validators.required]),
    category_id: this.fb.control<number | null>(this.data.installment?.category_id ?? null),
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
    const request$ = this.data.installment
      ? this.installments.update(this.data.workspaceId, this.data.installment.id, {
          description: value.description,
          category_id: value.category_id,
        })
      : this.installments.create(this.data.workspaceId, {
          description: value.description,
          total_amount: value.total_amount ?? 0,
          installments_count: value.installments_count ?? 0,
          start_date: value.start_date,
          category_id: value.category_id,
        });

    request$.subscribe({
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

  private addMonths(date: string, months: number): string {
    const [year, month, day] = date.split('-').map(Number);
    const result = new Date(year, month - 1 + months, day);

    return `${result.getFullYear()}-${String(result.getMonth() + 1).padStart(2, '0')}-${String(result.getDate()).padStart(2, '0')}`;
  }
}
