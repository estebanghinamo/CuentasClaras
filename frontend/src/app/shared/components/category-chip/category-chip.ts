import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { CategoryDto } from '../../../core/models/category.models';

/** Pill con ícono + color + nombre de una categoría. Reutilizable en listas de
 * gastos/presupuestos más adelante (M-06, M-12), no solo en features/categories. */
@Component({
  selector: 'app-category-chip',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './category-chip.html',
  styleUrl: './category-chip.scss',
})
export class CategoryChip {
  readonly category = input.required<CategoryDto>();

  protected softColor(): string {
    return `color-mix(in srgb, ${this.category().color} 18%, transparent)`;
  }
}
