import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatAutocompleteModule, MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatChipsModule } from '@angular/material/chips';
import { MatDialog } from '@angular/material/dialog';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatTooltipModule } from '@angular/material/tooltip';
import { CategoryDto } from '../../../../core/models/category.models';
import { ExpenseDto, ExpenseListDto, PAYMENT_METHOD_LABELS } from '../../../../core/models/expense.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { ToastService } from '../../../../core/services/toast.service';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { CategoriesService } from '../../../categories/categories.service';
import { CategoryManagerPanel } from '../../../categories/components/category-manager-panel/category-manager-panel';
import { MonthYearPeriod, MonthYearPicker } from '../../../../shared/components/month-year-picker/month-year-picker';
import { ExpensesResource } from '../../expenses.resource';
import { ExpenseDetailsDialog, ExpenseDetailsDialogData } from '../../components/expense-details-dialog/expense-details-dialog';
import { ExpenseFormDialog, ExpenseFormDialogData } from '../../components/expense-form-dialog/expense-form-dialog';

@Component({
  selector: 'app-expenses-page',
  imports: [
    MatAutocompleteModule,
    MatButtonModule,
    MatChipsModule,
    MatExpansionModule,
    MatFormFieldModule,
    MatTooltipModule,
    CategoryManagerPanel,
    MonthYearPicker,
    CurrencyPipe,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './expenses.page.html',
  styleUrl: './expenses.page.scss',
})
export class ExpensesPage {
  readonly summary = input.required<ExpenseListDto>();

  private readonly route = inject(ActivatedRoute);
  private readonly expenses = inject(ExpensesResource);
  private readonly categoriesService = inject(CategoriesService);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly items = signal<ExpenseDto[]>([]);
  readonly meta = signal<ExpenseListDto['meta'] | null>(null);
  readonly categories = signal<CategoryDto[]>([]);
  readonly selectedCategoryIds = signal<number[]>([]);
  readonly categorySearch = signal('');

  readonly selectedCategories = computed(() =>
    this.selectedCategoryIds()
      .map((id) => this.categories().find((c) => c.id === id))
      .filter((c): c is CategoryDto => !!c),
  );

  readonly filteredCategories = computed(() => {
    const term = this.categorySearch().trim().toLowerCase();
    const available = this.categories().filter((c) => !this.selectedCategoryIds().includes(c.id));
    return term ? available.filter((c) => c.name.toLowerCase().includes(term)) : available;
  });

  private readonly now = new Date();
  readonly year = signal(this.now.getFullYear());
  readonly month = signal(this.now.getMonth() + 1);
  private page = 1;

  constructor() {
    effect(
      () => {
        this.items.set(this.summary().items);
        this.meta.set(this.summary().meta);
      },
      { allowSignalWrites: true },
    );
    this.categoriesService.list(this.workspaceId).subscribe((categories) => this.categories.set(categories));
  }

  reloadCategories(): void {
    this.categoriesService.list(this.workspaceId).subscribe((categories) => this.categories.set(categories));
  }

  paymentMethodLabel(expense: ExpenseDto): string {
    return PAYMENT_METHOD_LABELS[expense.payment_method];
  }

  recentDescriptions(): string[] {
    return [...new Set(this.items().map((e) => e.description).filter((d): d is string => !!d))].slice(0, 6);
  }


  goToPeriod(period: MonthYearPeriod): void {
    this.year.set(period.year);
    this.month.set(period.month);
    this.page = 1;
    this.reload();
  }

  onCategorySearchInput(value: string): void {
    this.categorySearch.set(value);
  }

  onCategorySelected(event: MatAutocompleteSelectedEvent): void {
    const categoryId = event.option.value as number;
    this.selectedCategoryIds.update((ids) => [...ids, categoryId]);
    this.categorySearch.set('');
    this.page = 1;
    this.reload();
  }

  removeCategoryFilter(categoryId: number): void {
    this.selectedCategoryIds.update((ids) => ids.filter((id) => id !== categoryId));
    this.page = 1;
    this.reload();
  }

  changePage(delta: number): void {
    this.page += delta;
    this.reload();
  }

  openForm(expense: ExpenseDto | null): void {
    const ref = this.dialog.open<ExpenseFormDialog, ExpenseFormDialogData, ExpenseDto | undefined>(ExpenseFormDialog, {
      data: {
        workspaceId: this.workspaceId,
        expense,
        categories: this.categories(),
        recentDescriptions: this.recentDescriptions(),
      },
      width: '480px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(expense ? 'Gasto actualizado.' : 'Gasto cargado.');
        this.reload();
      }
    });
  }

  openDetails(expense: ExpenseDto): void {
    this.dialog.open<ExpenseDetailsDialog, ExpenseDetailsDialogData>(ExpenseDetailsDialog, {
      data: { expense },
      width: '480px',
    });
  }

  delete(expense: ExpenseDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Eliminar gasto',
          message: `¿Eliminar el gasto${expense.category_name ? ' de ' + expense.category_name : ''}?`,
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
        this.expenses.delete(this.workspaceId, expense.id).subscribe({
          next: () => {
            this.toast.success('Gasto eliminado.');
            this.reload();
          },
        });
      });
  }

  private monthRange(): { date_from: string; date_to: string } {
    const from = new Date(this.year(), this.month() - 1, 1);
    const to = new Date(this.year(), this.month(), 0);
    const fmt = (d: Date) => d.toISOString().slice(0, 10);

    return { date_from: fmt(from), date_to: fmt(to) };
  }

  private reload(): void {
    this.expenses
      .list(this.workspaceId, {
        ...this.monthRange(),
        page: this.page,
        per_page: 20,
        category_ids: this.selectedCategoryIds().length > 0 ? this.selectedCategoryIds() : undefined,
      })
      .subscribe((result) => {
        this.items.set(result.items);
        this.meta.set(result.meta);
      });
  }
}
