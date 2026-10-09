import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { MatButtonToggleModule } from '@angular/material/button-toggle';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { LateFeeType } from '../../../../core/models/service.models';
import { DayOfMonthPicker } from '../../../../shared/components/day-of-month-picker/day-of-month-picker';

// Subconjunto de campos que ServiceFormDialog y AcceptSuggestionDialog
// comparten literalmente igual (ver ambos .ts): cada uno arma su propio
// FormGroup con estos controles más los que le son propios (p.ej. "active").
export interface ServiceFieldsFormGroup extends FormGroup {
  controls: {
    name: FormControl<string>;
    amount: FormControl<number | null>;
    is_estimated: FormControl<boolean>;
    due_day_start: FormControl<number | null>;
    due_day_end: FormControl<number | null>;
    late_fee_type: FormControl<LateFeeType>;
    late_fee_value: FormControl<number | null>;
  };
}

/**
 * Campos comunes a ServiceFormDialog (crear/editar Service, M-08) y
 * AcceptSuggestionDialog (crear Service desde una sugerencia, M-18): nombre,
 * monto, "monto estimado", día(s) de vencimiento y recargo por mora. Ambos
 * dialogos comparten el mismo CreateServiceRequest en el backend (ver
 * accept-suggestion-dialog.ts) - lo unico que difiere entre ellos son el
 * título, el checkbox "Activo" (solo en edición) y el texto del botón, que
 * quedan en cada dialogo. El FormGroup lo arma y valida el componente padre;
 * este solo lo renderiza, para no duplicar la lógica de submit/validación.
 */
@Component({
  selector: 'app-service-fields-form',
  imports: [ReactiveFormsModule, MatButtonToggleModule, MatCheckboxModule, MatFormFieldModule, MatInputModule, DayOfMonthPicker],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './service-fields-form.html',
})
export class ServiceFieldsForm {
  readonly form = input.required<ServiceFieldsFormGroup>();
  readonly fieldError = input.required<(field: string) => string | null>();

  selectDay(field: 'due_day_start' | 'due_day_end', day: number | null): void {
    this.form().controls[field].setValue(day);
  }
}
