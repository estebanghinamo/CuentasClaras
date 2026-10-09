import { HttpContext, HttpContextToken } from '@angular/common/http';

/**
 * Requests silenciosos que no deben disparar el loader global (overlay con
 * backdrop) - pensado para polling de fondo (ej. NotificationService cada
 * 60s), donde mostrar el loader es molesto porque el usuario no inició la
 * acción. Uso: `this.http.get(url, { context: skipLoading() })`.
 */
export const SKIP_LOADING = new HttpContextToken<boolean>(() => false);

export function skipLoading(): HttpContext {
  return new HttpContext().set(SKIP_LOADING, true);
}
