import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { CategoryDto } from '../../../../core/models/category.models';
import { CategoriesService } from '../../categories.service';
import { fieldError } from '../../../../shared/utils/api-error.util';

export interface CategoryFormDialogData {
  workspaceId: number;
  category: CategoryDto | null;
}

// ~40 Material Symbols cubriendo los rubros más comunes de gastos personales.
const ICONS = [
  'shopping_cart', 'local_grocery_store', 'restaurant', 'fastfood', 'local_cafe',
  'directions_bus', 'directions_car', 'local_gas_station', 'flight', 'hotel',
  'home', 'build', 'cleaning_services', 'local_laundry_service',
  'medical_services', 'local_pharmacy', 'spa', 'fitness_center',
  'movie', 'sports_esports', 'sports_soccer', 'theater_comedy', 'music_note', 'book', 'celebration', 'park', 'pool',
  'checkroom', 'school', 'child_care', 'pets', 'card_giftcard',
  'savings', 'credit_card', 'account_balance', 'receipt_long',
  'computer', 'phone_iphone', 'wifi', 'more_horiz',
];

// Paleta de 16 colores (Material Design estándar) + input hex para uno custom.
const COLORS = [
  '#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4',
  '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFC107', '#FF9800', '#795548', '#9E9E9E',
];

@Component({
  selector: 'app-category-form-dialog',
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    Spinner,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './category-form-dialog.html',
  styleUrl: './category-form-dialog.scss',
})
export class CategoryFormDialog {
  protected readonly data = inject<CategoryFormDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<CategoryFormDialog>);
  private readonly fb = inject(FormBuilder);
  private readonly categories = inject(CategoriesService);

  protected readonly icons = ICONS;
  protected readonly colors = COLORS;

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.nonNullable.group({
    name: [this.data.category?.name ?? '', [Validators.required, Validators.minLength(1), Validators.maxLength(100)]],
    icon: [this.data.category?.icon ?? ICONS[0], [Validators.required]],
    color: [
      this.data.category?.color ?? COLORS[0],
      [Validators.required, Validators.pattern(/^#[0-9A-Fa-f]{6}$/)],
    ],
  });

  submit(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    const request$ = this.data.category
      ? this.categories.update(this.data.workspaceId, this.data.category.id, this.form.getRawValue())
      : this.categories.create(this.data.workspaceId, this.form.getRawValue());

    request$.subscribe({
      next: (category) => {
        this.saving.set(false);
        this.dialogRef.close(category);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }
}
