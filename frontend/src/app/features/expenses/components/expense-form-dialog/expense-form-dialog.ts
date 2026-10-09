import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatAutocompleteModule, MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatButtonToggleModule } from '@angular/material/button-toggle';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { CategoriesService } from '../../../categories/categories.service';
import { CategoryFormDialog, CategoryFormDialogData } from '../../../categories/components/category-form-dialog/category-form-dialog';
import { CategoryDto } from '../../../../core/models/category.models';
import { ExpenseDto, PAYMENT_METHOD_LABELS, PaymentMethod } from '../../../../core/models/expense.models';
import { ExpensesResource } from '../../expenses.resource';
import { fieldError } from '../../../../shared/utils/api-error.util';
import { todayLocalIso } from '../../../../shared/utils/date.util';

export interface ExpenseFormDialogData {
  workspaceId: number;
  expense: ExpenseDto | null;
  categories: CategoryDto[];
  recentDescriptions: string[];
}

const PAYMENT_METHODS: PaymentMethod[] = ['cash', 'debit', 'credit', 'transfer', 'wallet', 'other'];

@Component({
  selector: 'app-expense-form-dialog',
  imports: [
    ReactiveFormsModule,
    FormsModule,
    MatDialogModule,
    MatAutocompleteModule,
    MatButtonModule,
    MatButtonToggleModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    Spinner,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './expense-form-dialog.html',
  styleUrl: './expense-form-dialog.scss',
})
export class ExpenseFormDialog {
  protected readonly data = inject<ExpenseFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<ExpenseFormDialog>);
  private readonly fb = inject(FormBuilder);
  private readonly expenses = inject(ExpensesResource);
  private readonly dialog = inject(MatDialog);
  private readonly categoriesService = inject(CategoriesService);

  protected readonly paymentMethods = PAYMENT_METHODS;
  selectedCategoryId: number | null = this.data.expense?.category_id ?? null;

  readonly categories = signal<CategoryDto[]>(this.data.categories);
  readonly categorySearch = signal(this.initialCategoryName());
  readonly filteredCategories = computed(() => {
    const term = this.categorySearch().trim().toLowerCase();
    return term ? this.categories().filter((c) => c.name.toLowerCase().includes(term)) : this.categories();
  });

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.group({
    amount: this.fb.control<number | null>(this.data.expense?.amount ?? null, [Validators.required, Validators.min(0.01)]),
    date: this.fb.nonNullable.control(this.data.expense?.date ?? todayLocalIso(), [Validators.required]),
    payment_method: this.fb.nonNullable.control<PaymentMethod>(this.data.expense?.payment_method ?? 'cash', [Validators.required]),
    description: this.fb.control<string | null>(this.data.expense?.description ?? null, [Validators.maxLength(255)]),
  });

  // Sugiere descripciones ya usadas solo cuando se parecen a lo que se está
  // escribiendo (a pedido del usuario: antes aparecían todas sueltas como
  // chips fijos abajo del formulario, sin relación con lo que se tipeaba).
  private readonly descriptionValue = toSignal(this.form.controls.description.valueChanges, {
    initialValue: this.form.controls.description.value,
  });

  readonly filteredDescriptions = computed(() => {
    const term = (this.descriptionValue() ?? '').trim().toLowerCase();
    if (!term) {
      return [];
    }
    return this.data.recentDescriptions.filter((d) => d.toLowerCase().includes(term) && d.toLowerCase() !== term);
  });

  paymentMethodLabel(method: PaymentMethod): string {
    return PAYMENT_METHOD_LABELS[method];
  }

  onCategorySearchInput(value: string): void {
    this.categorySearch.set(value);
  }

  onCategorySelected(event: MatAutocompleteSelectedEvent): void {
    const categoryId = event.option.value as number;
    const category = this.categories().find((c) => c.id === categoryId);
    this.selectedCategoryId = categoryId;
    this.categorySearch.set(category?.name ?? '');
  }

  clearCategory(): void {
    this.selectedCategoryId = null;
    this.categorySearch.set('');
  }

  openCategoryForm(): void {
    const ref = this.dialog.open<CategoryFormDialog, CategoryFormDialogData, CategoryDto | undefined>(CategoryFormDialog, {
      data: { workspaceId: this.data.workspaceId, category: null },
      width: '480px',
    });

    ref.afterClosed().subscribe((category) => {
      if (category) {
        this.categoriesService.invalidate(this.data.workspaceId);
        this.categories.update((items) => [...items, category]);
        this.selectedCategoryId = category.id;
        this.categorySearch.set(category.name);
      }
    });
  }

  private initialCategoryName(): string {
    return this.data.categories.find((c) => c.id === this.data.expense?.category_id)?.name ?? '';
  }

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    const raw = this.form.getRawValue();
    const value = {
      amount: raw.amount ?? 0,
      date: raw.date,
      payment_method: raw.payment_method,
      description: raw.description || null,
      category_id: this.selectedCategoryId,
    };

    const request$ = this.data.expense
      ? this.expenses.update(this.data.workspaceId, this.data.expense.id, value)
      : this.expenses.create(this.data.workspaceId, value);

    request$.subscribe({
      next: (expense) => {
        this.saving.set(false);
        this.dialogRef.close(expense);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }
}
