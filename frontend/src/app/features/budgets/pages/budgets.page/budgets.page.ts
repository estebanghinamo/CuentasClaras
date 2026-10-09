import { DecimalPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { BudgetAlertLevel, BudgetDto, BudgetSummaryDto } from '../../../../core/models/budget.models';
import { CategoryDto } from '../../../../core/models/category.models';
import { ToastService } from '../../../../core/services/toast.service';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { BudgetFormDialog, BudgetFormDialogData } from '../../components/budget-form-dialog/budget-form-dialog';
import { BudgetsPageData } from '../../budgets.resolver';
import { BudgetsResource } from '../../budgets.resource';

const PROGRESS_CLASS: Record<BudgetAlertLevel, string> = {
  none: 'is-ok',
  warning: 'is-warning',
  reached: 'is-reached',
  exceeded: 'is-exceeded',
};

@Component({
  selector: 'app-budgets-page',
  imports: [MatButtonModule, DecimalPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './budgets.page.html',
  styleUrl: './budgets.page.scss',
})
export class BudgetsPage {
  readonly budgetsData = input.required<BudgetsPageData>();

  private readonly route = inject(ActivatedRoute);
  private readonly resource = inject(BudgetsResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly summary = signal<BudgetSummaryDto | null>(null);
  readonly categories = signal<CategoryDto[]>([]);
  readonly year = signal(0);
  readonly month = signal(0);

  readonly budgets = signal<BudgetDto[]>([]);
  readonly categoriesWithoutBudget = signal<BudgetSummaryDto['categories_without_budget']>([]);

  constructor() {
    effect(
      () => {
        const data = this.budgetsData();
        this.summary.set(data.summary);
        this.categories.set(data.categories);
        this.year.set(data.year);
        this.month.set(data.month);
        this.budgets.set(data.summary.budgets);
        this.categoriesWithoutBudget.set(data.summary.categories_without_budget);
      },
      { allowSignalWrites: true },
    );
  }

  progressClass(level: BudgetAlertLevel): string {
    return PROGRESS_CLASS[level];
  }

  openForm(budget: BudgetDto | null): void {
    this.openFormDialog(budget, this.categories());
  }

  openFormForCategory(category: { id: number; name: string; spent_amount: number }): void {
    const availableCategories = this.categories().filter((c) => c.id === category.id);
    this.openFormDialog(null, availableCategories);
  }

  private openFormDialog(budget: BudgetDto | null, categories: CategoryDto[]): void {
    const ref = this.dialog.open<BudgetFormDialog, BudgetFormDialogData, BudgetDto | undefined>(BudgetFormDialog, {
      data: { workspaceId: this.workspaceId, year: this.year(), month: this.month(), budget, categories },
      width: '420px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(budget ? 'Presupuesto actualizado.' : 'Presupuesto creado.');
        this.reload();
      }
    });
  }

  deleteBudget(budget: BudgetDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Eliminar presupuesto',
          message: `¿Eliminar el presupuesto de "${budget.category_name}"?`,
          confirmLabel: 'Eliminar',
          cancelLabel: 'Cancelar',
          icon: 'delete',
          tone: 'danger',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }

        this.resource.delete(this.workspaceId, budget.id).subscribe({
          next: () => {
            this.toast.success('Presupuesto eliminado.');
            this.reload();
          },
        });
      });
  }

  copyFromPreviousMonth(): void {
    const previous = new Date(this.year(), this.month() - 2, 1);

    this.resource
      .copyFromPeriod(this.workspaceId, {
        from_year: previous.getFullYear(),
        from_month: previous.getMonth() + 1,
        to_year: this.year(),
        to_month: this.month(),
        overwrite: false,
      })
      .subscribe((result) => {
        this.toast.success(`Se copiaron ${result.copied_count} presupuesto(s).`);
        if (result.copied_count > 0) {
          this.reload();
        }
      });
  }

  private reload(): void {
    this.resource.list(this.workspaceId, this.year(), this.month()).subscribe((summary) => {
      this.summary.set(summary);
      this.budgets.set(summary.budgets);
      this.categoriesWithoutBudget.set(summary.categories_without_budget);
    });
  }
}
