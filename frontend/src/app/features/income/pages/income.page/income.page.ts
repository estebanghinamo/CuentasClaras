import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { IncomeEntryDto, IncomeListDto } from '../../../../core/models/income.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { ToastService } from '../../../../core/services/toast.service';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { MonthYearPeriod, MonthYearPicker } from '../../../../shared/components/month-year-picker/month-year-picker';
import { IncomeResource } from '../../income.resource';
import { IncomeFormDialog, IncomeFormDialogData } from '../../components/income-form-dialog/income-form-dialog';

@Component({
  selector: 'app-income-page',
  imports: [MatButtonModule, CurrencyPipe, MonthYearPicker],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './income.page.html',
  styleUrl: './income.page.scss',
})
export class IncomePage {
  readonly summary = input.required<IncomeListDto>();

  private readonly route = inject(ActivatedRoute);
  private readonly income = inject(IncomeResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);

  private readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly items = signal<IncomeEntryDto[]>([]);
  readonly total = signal(0);

  private readonly now = new Date();
  readonly year = signal(this.now.getFullYear());
  readonly month = signal(this.now.getMonth() + 1);

  constructor() {
    effect(
      () => {
        this.items.set(this.summary().entries);
        this.total.set(this.summary().total);
      },
      { allowSignalWrites: true },
    );
  }

  isCurrentMonth(): boolean {
    return this.year() === this.now.getFullYear() && this.month() === this.now.getMonth() + 1;
  }

  goToPeriod(period: MonthYearPeriod): void {
    this.year.set(period.year);
    this.month.set(period.month);
    this.reload();
  }

  openForm(entry: IncomeEntryDto | null): void {
    const ref = this.dialog.open<IncomeFormDialog, IncomeFormDialogData, IncomeEntryDto | undefined>(IncomeFormDialog, {
      data: { workspaceId: this.workspaceId, entry },
      width: '480px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(entry ? 'Ingreso actualizado.' : 'Ingreso cargado.');
        this.reload();
      }
    });
  }

  delete(entry: IncomeEntryDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Eliminar ingreso',
          message: `¿Eliminar el ingreso "${entry.concept}"?`,
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
        this.income.delete(this.workspaceId, entry.id).subscribe({
          next: () => {
            this.toast.success('Ingreso eliminado.');
            this.reload();
          },
        });
      });
  }

  showClosingAllocationInfo(): void {
    this.dialog.open(ConfirmDialog, {
      width: '420px',
      data: {
        title: 'Asignación de un cierre mensual',
        message:
          'Este ingreso es la asignación del sobrante de un cierre mensual, un monto fijo que no se puede '
          + 'editar ni eliminar acá. Si te equivocaste, podés ir a Monedero o a Metas y aportar ese mismo '
          + 'monto desde el Disponible.',
        confirmLabel: 'Entendido',
        icon: 'info',
      },
    });
  }

  private reload(): void {
    this.income.list(this.workspaceId, this.year(), this.month()).subscribe((summary) => {
      this.items.set(summary.entries);
      this.total.set(summary.total);
    });
  }
}
