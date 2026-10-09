import { ChangeDetectionStrategy, Component, ElementRef, OnDestroy, effect, input, viewChild } from '@angular/core';
import { Chart, ChartConfiguration, registerables } from 'chart.js';

Chart.register(...registerables);

/**
 * Gráfico de línea genérico (Chart.js) pensado para reutilizarse en M-10
 * (historial del monedero) y M-14 (dashboard completo). Lee los colores del
 * sistema de diseño propio (--cc-*) en vez de hardcodear una paleta, para
 * respetar el tema claro/oscuro activo en el momento de renderizar.
 */
@Component({
  selector: 'app-line-chart',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './line-chart.html',
  styleUrl: './line-chart.scss',
})
export class LineChart implements OnDestroy {
  readonly labels = input.required<string[]>();
  readonly data = input.required<number[]>();

  private readonly canvasRef = viewChild.required<ElementRef<HTMLCanvasElement>>('canvas');
  private chart: Chart<'line'> | null = null;

  constructor() {
    effect(() => this.render(this.labels(), this.data()));
  }

  ngOnDestroy(): void {
    this.chart?.destroy();
  }

  private render(labels: string[], data: number[]): void {
    if (this.chart) {
      this.chart.data.labels = labels;
      this.chart.data.datasets[0].data = data;
      this.chart.update();

      return;
    }

    const styles = getComputedStyle(document.documentElement);
    const primary = styles.getPropertyValue('--cc-primary').trim() || '#4f46e5';
    const text2 = styles.getPropertyValue('--cc-text-2').trim() || '#64748b';
    const divider = styles.getPropertyValue('--cc-divider').trim() || '#e2e8f0';

    const config: ChartConfiguration<'line'> = {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            data,
            borderColor: primary,
            backgroundColor: `color-mix(in srgb, ${primary} 15%, transparent)`,
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: primary,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: text2 } },
          y: { grid: { color: divider }, ticks: { color: text2 } },
        },
      },
    };

    this.chart = new Chart(this.canvasRef().nativeElement, config);
  }
}
