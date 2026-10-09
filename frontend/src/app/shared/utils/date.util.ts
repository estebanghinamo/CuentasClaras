/** Nombres de mes en español, índice 0 = Enero - usado por cualquier página con selector de período (dashboard, cierres). */
export const MONTH_LABELS = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

/**
 * Fecha de HOY en horario local, formato Y-m-d. `new Date().toISOString()`
 * usa UTC: para un usuario en un huso horario negativo (ej. Argentina, UTC-3)
 * durante la noche (21hs a medianoche local), la fecha en UTC ya avanzó al
 * día siguiente - el backend entonces rechaza esa fecha como "futura" contra
 * `before_or_equal:today` (que corre en horario del servidor, Argentina) - ver
 * ERROR_LOG.md.
 */
export function todayLocalIso(): string {
  return dateToLocalIso(new Date());
}

/** Mismo criterio que `todayLocalIso()` pero para una fecha arbitraria (ej. la
 * elegida en un `<mat-datepicker>`, que entrega un `Date` en horario local). */
export function dateToLocalIso(date: Date): string {
  const localTime = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

  return localTime.toISOString().slice(0, 10);
}

/** Inverso de `dateToLocalIso()`: parsea un 'Y-m-d' como fecha LOCAL, no UTC -
 * `new Date('2026-10-05')` interpreta el string como medianoche UTC, que en
 * Argentina (UTC-3) cae el día 4 a las 21hs, corriendo la fecha un día para
 * atrás apenas se lee con getDate()/getMonth() en horario local. */
export function parseLocalIso(dateStr: string): Date {
  const [year, month, day] = dateStr.split('-').map(Number);

  return new Date(year, month - 1, day);
}

/** Un día después de hoy, en horario local (medianoche). Útil como `[min]` de
 * un `<mat-datepicker>` cuando el backend exige `after:today` (estrictamente
 * posterior, no incluye hoy). */
export function tomorrowLocalDate(): Date {
  const date = parseLocalIso(todayLocalIso());
  date.setDate(date.getDate() + 1);

  return date;
}
