import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatTabsModule } from '@angular/material/tabs';
import { ServiceDto, ServicePaymentDto, ServicePaymentListDto } from '../../../../core/models/service.models';
import { ConfirmDialog } from '../../../../shared/components/confirm-dialog/confirm-dialog';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { ToastService } from '../../../../core/services/toast.service';
import { MonthYearPeriod, MonthYearPicker } from '../../../../shared/components/month-year-picker/month-year-picker';
import { ServicePaymentsResource } from '../../service-payments.resource';
import { ServicesResource } from '../../services.resource';
import { ServicesPageData } from '../../services.resolver';
import { PayServiceDialog, PayServiceDialogData } from '../../components/pay-service-dialog/pay-service-dialog';
import { ServiceFormDialog, ServiceFormDialogData } from '../../components/service-form-dialog/service-form-dialog';

const STATUS_LABELS: Record<ServicePaymentDto['status'], string> = {
  pending: 'Pendiente',
  paid: 'Pagado',
  overdue: 'Vencido',
};

@Component({
  selector: 'app-services-page',
  imports: [MatButtonModule, MatTabsModule, CurrencyPipe, MonthYearPicker],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './services.page.html',
  styleUrl: './services.page.scss',
})
export class ServicesPage {
  readonly servicesData = input.required<ServicesPageData>();

  private readonly route = inject(ActivatedRoute);
  private readonly services$ = inject(ServicesResource);
  private readonly servicePayments$ = inject(ServicePaymentsResource);
  private readonly dialog = inject(MatDialog);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);

  private readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly services = signal<ServiceDto[]>([]);
  readonly payments = signal<ServicePaymentListDto | null>(null);
  readonly overdue = signal<ServicePaymentDto[]>([]);

  private readonly now = new Date();
  readonly year = signal(this.now.getFullYear());
  readonly month = signal(this.now.getMonth() + 1);

  constructor() {
    effect(() => {
      this.services.set(this.servicesData().services);
      this.payments.set(this.servicesData().payments);
    }, { allowSignalWrites: true });
  }

  statusLabel(status: ServicePaymentDto['status']): string {
    return STATUS_LABELS[status];
  }

  statusClass(status: ServicePaymentDto['status']): string {
    return `is-${status}`;
  }

  paymentAmount(item: ServicePaymentDto): number {
    return item.expected_amount;
  }

  paymentDifference(item: ServicePaymentDto): number {
    return item.status === 'paid' && item.amount_paid !== null ? item.amount_paid - item.expected_amount : 0;
  }

  paymentDifferenceLabel(item: ServicePaymentDto): string {
    return this.paymentDifference(item) > 0 ? 'Extra' : 'Ajuste';
  }

  absolutePaymentDifference(item: ServicePaymentDto): number {
    return Math.abs(this.paymentDifference(item));
  }

  onTabChange(index: number): void {
    if (index === 2) {
      this.servicePayments$.overdue(this.workspaceId).subscribe((items) => this.overdue.set(items));
    }
  }

  goToPeriod(period: MonthYearPeriod): void {
    this.year.set(period.year);
    this.month.set(period.month);
    this.servicePayments$.listForPeriod(this.workspaceId, period.year, period.month).subscribe((data) => this.payments.set(data));
  }

  openForm(service: ServiceDto | null): void {
    const ref = this.dialog.open<ServiceFormDialog, ServiceFormDialogData, ServiceDto | undefined>(ServiceFormDialog, {
      data: { workspaceId: this.workspaceId, service },
      width: '480px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success(service ? 'Servicio actualizado.' : 'Servicio creado.');
        this.reloadServices();
        this.reloadCurrentPeriod();
      }
    });
  }

  openPay(payment: ServicePaymentDto): void {
    const service = this.services().find((s) => s.id === payment.service_id);

    const ref = this.dialog.open<PayServiceDialog, PayServiceDialogData, ServicePaymentDto | undefined>(PayServiceDialog, {
      data: {
        workspaceId: this.workspaceId,
        payment,
        lateFeeType: service?.late_fee_type ?? 'fixed',
        lateFeeValue: service?.late_fee_value ?? 0,
      },
      width: '480px',
    });

    ref.afterClosed().subscribe((result) => {
      if (result) {
        this.toast.success('Servicio marcado como pagado.');
        this.reloadCurrentPeriod();
        this.overdue.update((items) => items.filter((i) => i.id !== payment.id));
      }
    });
  }

  unpay(payment: ServicePaymentDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Deshacer pago',
          message: `¿Deshacer el pago de ${payment.service_name}?`,
          confirmLabel: 'Deshacer',
          cancelLabel: 'Volver',
          icon: 'undo',
        },
      })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.servicePayments$.unpay(this.workspaceId, payment.id).subscribe({
          next: () => {
            this.toast.success('Pago deshecho.');
            this.reloadCurrentPeriod();
          },
        });
      });
  }

  delete(service: ServiceDto): void {
    this.dialog
      .open(ConfirmDialog, {
        width: '420px',
        data: {
          title: 'Eliminar servicio',
          message: `¿Eliminar ${service.name}? Se borrará el historial de pagos. Si querés conservarlo, desactivalo.`,
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
        this.services$.delete(this.workspaceId, service.id).subscribe({
          next: () => {
            this.toast.success('Servicio eliminado.');
            this.reloadServices();
            this.reloadCurrentPeriod();
          },
        });
      });
  }

  private reloadServices(): void {
    this.services$.list(this.workspaceId, 'all').subscribe((items) => this.services.set(items));
  }

  private reloadCurrentPeriod(): void {
    this.servicePayments$.listForPeriod(this.workspaceId, this.year(), this.month()).subscribe((data) => this.payments.set(data));
  }
}
