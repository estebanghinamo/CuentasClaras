import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HealthBreakdownDto } from '../../../core/models/health-score.models';
import { HealthGauge } from './health-gauge';

const BREAKDOWN: HealthBreakdownDto = {
  budgets: { score: 70, weight: 30, budgets_count: 0, within_limit_count: 0, detail: 'Sin presupuestos definidos' },
  savings: { score: 22, weight: 30, savings_rate_pct: 4.3, detail: 'Ahorraste 4.3% del ingreso' },
  services_on_time: { score: 63, weight: 20, paid_on_time: 1, paid_late: 3, overdue: 0, detail: '1 a tiempo, 3 con mora, 0 vencidos' },
  installments_load: { score: 50, weight: 20, load_pct: 25, detail: '25.0% del ingreso en cuotas' },
};

describe('HealthGauge', () => {
  let component: HealthGauge;
  let fixture: ComponentFixture<HealthGauge>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HealthGauge],
    }).compileComponents();

    fixture = TestBed.createComponent(HealthGauge);
    component = fixture.componentInstance;
  });

  it('should create', () => {
    fixture.detectChanges();
    expect(component).toBeTruthy();
  });

  it('shows the empty state when score is null', () => {
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Sin datos suficientes para calcular el puntaje este mes');
    expect(component.dashOffset()).toBe(component.circumference);
  });

  it('computes dashOffset proportional to the score', () => {
    fixture.componentRef.setInput('score', 50);
    fixture.componentRef.setInput('level', 'warning');
    fixture.detectChanges();

    expect(component.dashOffset()).toBeCloseTo(component.circumference * 0.5, 5);
  });

  it('toggles the breakdown detail and maps each row to its label/explanation', () => {
    fixture.componentRef.setInput('score', 50);
    fixture.componentRef.setInput('level', 'warning');
    fixture.componentRef.setInput('breakdown', BREAKDOWN);
    fixture.detectChanges();

    expect(component.expanded()).toBe(false);
    expect(component.breakdownRows()).toHaveSize(4);
    expect(component.breakdownRows()[1].label).toBe('% del ingreso ahorrado');

    component.toggle();
    expect(component.expanded()).toBe(true);
  });
});
