import { CurrencyPipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, effect, inject, input, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { BudgetAlertLevel, BudgetSummaryDto } from '../../../../core/models/budget.models';
import { DashboardDto, DashboardTrend, HistoryPointDto } from '../../../../core/models/dashboard.models';
import { HealthGauge } from '../../../../shared/components/health-gauge/health-gauge';
import { LineChart } from '../../../../shared/components/line-chart/line-chart';
import { MonthYearPeriod, MonthYearPicker } from '../../../../shared/components/month-year-picker/month-year-picker';
import { MONTH_LABELS } from '../../../../shared/utils/date.util';
import { downloadBlob } from '../../../../shared/utils/download.util';
import { DashboardPageData } from '../../dashboard.resolver';
import { DashboardResource } from '../../dashboard.resource';

const MONTH_LABELS_SHORT = MONTH_LABELS.map((label) => label.slice(0, 3));

const TREND_LABELS: Record<DashboardTrend, string> = {
  under: 'Vas bien, por debajo de tu ritmo habitual.',
  on_track: 'Vas en línea con tu ritmo habitual.',
  over: 'Cuidado, vas a terminar el mes en negativo a este ritmo.',
};

const PROGRESS_CLASS: Record<BudgetAlertLevel, string> = {
  none: 'is-ok',
  warning: 'is-warning',
  reached: 'is-reached',
  exceeded: 'is-exceeded',
};

@Component({
  selector: 'app-dashboard-page',
  imports: [MatButtonModule, RouterLink, CurrencyPipe, HealthGauge, MonthYearPicker, LineChart],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './dashboard.page.html',
})
export class DashboardPage {
  readonly dashboardData = input.required<DashboardPageData>();

  private readonly route = inject(ActivatedRoute);
  private readonly dashboardResource = inject(DashboardResource);

  protected readonly workspaceId = Number(this.route.snapshot.paramMap.get('workspaceId'));

  readonly dashboard = signal<DashboardDto | null>(null);
  readonly history = signal<HistoryPointDto[]>([]);
  readonly budgets = signal<BudgetSummaryDto | null>(null);

  private readonly now = new Date();
  readonly year = signal(this.now.getFullYear());
  readonly month = signal(this.now.getMonth() + 1);

  readonly historyLabels = computed(() => this.history().map((point) => `${MONTH_LABELS_SHORT[point.month - 1]} ${point.year}`));
  readonly historyAvailable = computed(() => this.history().map((point) => point.available));

  // Listas del Dashboard recortadas a 5 filas con "Ver todas" para expandir
  // in-place - a pedido del usuario, evitar que una categoría/presupuesto con
  // muchas filas alargue demasiado el vistazo general.
  private static readonly LIST_LIMIT = 5;
  readonly categoriesExpanded = signal(false);
  readonly budgetsExpanded = signal(false);

  readonly visibleCategories = computed(() => {
    const rows = this.dashboard()?.by_category ?? [];
    return this.categoriesExpanded() ? rows : rows.slice(0, DashboardPage.LIST_LIMIT);
  });

  readonly visibleBudgets = computed(() => {
    const rows = this.budgets()?.budgets ?? [];
    return this.budgetsExpanded() ? rows : rows.slice(0, DashboardPage.LIST_LIMIT);
  });

  constructor() {
    effect(
      () => {
        const data = this.dashboardData();
        this.dashboard.set(data.dashboard);
        this.history.set(data.history);
        this.budgets.set(data.budgets);
      },
      { allowSignalWrites: true },
    );
  }

  pendingClosingLabel(): string {
    const closing = this.dashboard()?.pending_allocation;
    return closing ? `${MONTH_LABELS[closing.month - 1]} ${closing.year}` : '';
  }

  deltaLabel(pct: number | null): { text: string; up: boolean } | null {
    if (pct === null) {
      return null;
    }
    const up = pct >= 0;
    return { text: `${up ? '▲' : '▼'} ${Math.abs(pct)}% vs. mes anterior`, up };
  }

  isInstallmentsFullyPaid(): boolean {
    const total = this.dashboard()?.totals?.installments ?? 0;
    const paid = this.dashboard()?.totals?.installments_paid ?? 0;
    return total > 0 && paid >= total;
  }

  trendLabel(trend: DashboardTrend): string {
    return TREND_LABELS[trend];
  }

  progressClass(level: BudgetAlertLevel): string {
    return PROGRESS_CLASS[level];
  }

  goToPeriod(period: MonthYearPeriod): void {
    this.year.set(period.year);
    this.month.set(period.month);
    this.dashboardResource.get(this.workspaceId, period.year, period.month).subscribe((data) => this.dashboard.set(data));
  }

  exportAll(): void {
    this.dashboardResource.exportAll(this.workspaceId).subscribe((blob) => downloadBlob(blob, 'cuentas-claras.xlsx'));
  }
}
