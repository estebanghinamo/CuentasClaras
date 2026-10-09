import { ChangeDetectionStrategy, Component, inject, input, output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';
import { CategoryDto } from '../../../../core/models/category.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { ToastService } from '../../../../core/services/toast.service';
import { CategoriesService } from '../../categories.service';
import { CategoryFormDialog, CategoryFormDialogData } from '../category-form-dialog/category-form-dialog';

/**
 * Gestión completa de categorías (alta/edición/borrado), pensada para vivir
 * dentro de un panel expandible en Gastos en vez de una ruta propia (decisión
 * del usuario, 2026-09-18: "no me gusta que esté como sección").
 */
@Component({
  selector: 'app-category-manager-panel',
  imports: [MatButtonModule, MatTooltipModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './category-manager-panel.html',
  styleUrl: './category-manager-panel.scss',
})
export class CategoryManagerPanel {
  readonly workspaceId = input.required<number>();
  readonly categories = input.required<CategoryDto[]>();
  readonly changed = output<void>();

  private readonly categoriesService = inject(CategoriesService);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);

  softColor(category: CategoryDto): string {
    return `color-mix(in srgb, ${category.color} 16%, transparent)`;
  }

  openForm(category: CategoryDto | null): void {
    const ref = this.dialog.open<CategoryFormDialog, CategoryFormDialogData, CategoryDto | undefined>(
      CategoryFormDialog,
      { data: { workspaceId: this.workspaceId(), category }, width: '480px' },
    );

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(category ? 'Categoría actualizada.' : 'Categoría creada.');
        this.changed.emit();
      }
    });
  }

  delete(category: CategoryDto): void {
    const message =
      category.expenses_count > 0
        ? `Los ${category.expenses_count} gastos de "${category.name}" quedarán sin categoría. ¿Eliminar igual?`
        : `¿Eliminar la categoría "${category.name}"?`;

    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Eliminar categoría',
          message,
          confirmLabel: 'Eliminar',
          cancelLabel: 'Volver',
          icon: 'delete',
          tone: 'danger',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.categoriesService.delete(this.workspaceId(), category.id).subscribe({
          next: () => {
            this.toast.success('Categoría eliminada.');
            this.changed.emit();
          },
        });
      });
  }
}
