import { ChangeDetectionStrategy, Component, computed, input, signal } from '@angular/core';
import { MatTooltipModule } from '@angular/material/tooltip';
import { HealthBreakdownDto, HealthLevel } from '../../../core/models/health-score.models';

const LEVEL_LABELS: Record<HealthLevel, string> = {
  good: 'Saludable',
  warning: 'Atención',
  bad: 'Crítico',
};

const BREAKDOWN_LABELS: Record<keyof HealthBreakdownDto, string> = {
  budgets: 'Cumplimiento de presupuestos',
  savings: '% del ingreso ahorrado',
  services_on_time: 'Puntualidad pagando servicios',
  installments_load: '% del ingreso en cuotas',
};

const BREAKDOWN_EXPLANATIONS: Record<keyof HealthBreakdownDto, string> = {
  budgets: 'Qué tan bien te mantuviste dentro del límite de tus presupuestos por categoría este mes. Sin presupuestos cargados, este puntaje queda neutro (70).',
  savings: 'Qué porcentaje de tu ingreso del mes destinaste a ahorro (monedero + metas). Ahorrar un 20% del ingreso o más da el puntaje máximo.',
  services_on_time: 'De los servicios que pagaste este mes, cuántos pagaste a tiempo vs. con mora vs. vencidos. Pagar a tiempo suma, con mora resta a la mitad, vencido no suma nada.',
  installments_load: 'Qué porcentaje de tu ingreso se va en cuotas este mes. 10% o menos es ideal (100 puntos); 40% o más se considera una carga alta (0 puntos).',
};

interface BreakdownRow {
  key: keyof HealthBreakdownDto;
  label: string;
  explanation: string;
  score: number;
  weight: number;
  detail: string;
}

/**
 * Gauge semicircular en SVG puro (sin Chart.js: la única forma que soporta
 * es 'line', ver shared/components/line-chart.ts). DashboardDto.health se
 * calcula siempre en vivo para el período mostrado (DashboardService::get,
 * vía HealthScoreService::computeLive) - score null significa "mes sin datos"
 * (sin ingresos ni gastos), no "todavía no calculado".
 */
@Component({
  selector: 'app-health-gauge',
  imports: [MatTooltipModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './health-gauge.html',
  styleUrl: './health-gauge.scss',
})
export class HealthGauge {
  readonly score = input<number | null>(null);
  readonly level = input<HealthLevel | null>(null);
  readonly breakdown = input<HealthBreakdownDto | null>(null);

  readonly expanded = signal(false);

  private readonly radius = 70;
  readonly circumference = Math.PI * this.radius;
  readonly arcPath = `M 20 100 A ${this.radius} ${this.radius} 0 0 1 180 100`;

  readonly dashOffset = computed(() => {
    const value = this.score();
    if (value === null) {
      return this.circumference;
    }
    const pct = Math.max(0, Math.min(100, value)) / 100;
    return this.circumference * (1 - pct);
  });

  readonly levelLabel = computed(() => {
    const level = this.level();
    return level ? LEVEL_LABELS[level] : 'Sin calcular';
  });

  readonly breakdownRows = computed<BreakdownRow[]>(() => {
    const breakdown = this.breakdown();
    if (!breakdown) {
      return [];
    }

    return (Object.keys(BREAKDOWN_LABELS) as (keyof HealthBreakdownDto)[]).map((key) => {
      const item = breakdown[key];
      return {
        key,
        label: BREAKDOWN_LABELS[key],
        explanation: BREAKDOWN_EXPLANATIONS[key],
        score: item.score,
        weight: item.weight,
        detail: item.detail,
      };
    });
  });

  toggle(): void {
    if (this.breakdown()) {
      this.expanded.update((value) => !value);
    }
  }
}
