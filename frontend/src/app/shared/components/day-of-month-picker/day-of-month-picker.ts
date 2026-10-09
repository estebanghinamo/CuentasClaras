import { ChangeDetectionStrategy, Component, input, output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatMenuModule } from '@angular/material/menu';

export type DayOfMonthField = 'due_day_start' | 'due_day_end';

/**
 * Selector de "día del mes de vencimiento" (1-31), con menú tipo grilla.
 * Extraído de ServiceFormDialog/AcceptSuggestionDialog (M-08/M-18, que
 * comparten el mismo form de Service - ver accept-suggestion-dialog.ts),
 * donde este bloque estaba duplicado texto por texto.
 *
 * `allowClear` habilita el botón "Sin día límite" (solo tiene sentido para
 * due_day_end, que es opcional - due_day_start siempre requiere un valor).
 */
@Component({
  selector: 'app-day-of-month-picker',
  imports: [MatButtonModule, MatFormFieldModule, MatInputModule, MatMenuModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './day-of-month-picker.html',
})
export class DayOfMonthPicker {
  readonly label = input.required<string>();
  readonly day = input<number | null>(null);
  readonly allowClear = input(false);
  readonly errorMessage = input<string | null>(null);
  readonly dayChange = output<number | null>();

  readonly days = Array.from({ length: 31 }, (_, i) => i + 1);

  dayLabel(): string {
    const value = this.day();
    return value ? `Día ${value}` : '';
  }

  selectDay(day: number | null): void {
    this.dayChange.emit(day);
  }
}
