import { ChangeDetectionStrategy, Component, effect, input, output } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDatepicker, MatDatepickerModule } from '@angular/material/datepicker';
import { MONTH_LABELS } from '../../utils/date.util';

export interface MonthYearPeriod {
  year: number;
  month: number;
}

/**
 * Selector de período (año + mes, sin día) para páginas con navegación
 * mensual (Dashboard, Gastos, Cierres). Usa el truco oficial de Angular
 * Material para un "month/year picker": startView="multi-year" + manejar
 * (yearSelected)/(monthSelected) a mano, cerrando el calendario apenas se
 * elige el mes (nunca llega a mostrar la grilla de días).
 *
 * Incluye las flechas prev/next integradas (2026-09, a pedido del usuario:
 * antes eran 2 <button> sueltos alrededor del picker en cada página - en
 * mobile los 3 controles no entraban en una fila y se apilaban, feo). Ahora
 * es un solo control "‹ Septiembre 2026 ›" que cabe en una fila siempre.
 */
@Component({
  selector: 'app-month-year-picker',
  imports: [ReactiveFormsModule, MatButtonModule, MatDatepickerModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './month-year-picker.html',
  styleUrl: './month-year-picker.scss',
})
export class MonthYearPicker {
  readonly year = input.required<number>();
  readonly month = input.required<number>();
  readonly periodChange = output<MonthYearPeriod>();

  protected readonly control = new FormControl<Date | null>(null);

  constructor() {
    effect(() => this.control.setValue(new Date(this.year(), this.month() - 1, 1), { emitEvent: false }));
  }

  label(): string {
    return `${MONTH_LABELS[this.month() - 1]} ${this.year()}`;
  }

  shortLabel(): string {
    return `${MONTH_LABELS[this.month() - 1].slice(0, 3)} ${this.year()}`;
  }

  changeMonth(delta: number): void {
    let newMonth = this.month() + delta;
    let newYear = this.year();

    if (newMonth > 12) {
      newMonth = 1;
      newYear += 1;
    } else if (newMonth < 1) {
      newMonth = 12;
      newYear -= 1;
    }

    this.periodChange.emit({ year: newYear, month: newMonth });
  }

  chosenYearHandler(normalizedYear: Date): void {
    const current = this.control.value ?? normalizedYear;
    this.control.setValue(new Date(normalizedYear.getFullYear(), current.getMonth(), 1));
  }

  chosenMonthHandler(normalizedMonth: Date, datepicker: MatDatepicker<Date>): void {
    const current = this.control.value ?? normalizedMonth;
    const updated = new Date(current.getFullYear(), normalizedMonth.getMonth(), 1);
    this.control.setValue(updated);
    datepicker.close();
    this.periodChange.emit({ year: updated.getFullYear(), month: updated.getMonth() + 1 });
  }
}
