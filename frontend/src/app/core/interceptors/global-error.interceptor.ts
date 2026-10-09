import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { catchError, throwError } from 'rxjs';
import { ApiError } from '../models/api.models';
import { ConfirmDialog } from '../../shared/components/confirm-dialog/confirm-dialog';
import { formErrorMessage } from '../../shared/utils/api-error.util';

/**
 * Rutas con su propia UX de error ya resuelta (banner inline en la pantalla de
 * login/registro/2fa/reset, o redirección silenciosa de authGuard para /me) -
 * mostrar acá TAMBIÉN un modal genérico sería confuso (ej. TWO_FACTOR_REQUIRED
 * no es un error real, es parte del flujo normal de login).
 */
const SKIP_PATH_FRAGMENTS = ['/auth/'];

function isApiError(value: unknown): value is ApiError {
  return typeof value === 'object' && value !== null && (value as Partial<ApiError>).success === false;
}

/**
 * Muestra un modal (ConfirmDialog en modo alerta) para CUALQUIER llamada a la
 * API que termine en error - a pedido explícito del usuario ("el modal de
 * error lo quiero para cada endpoint"), en vez de depender de que cada
 * pantalla/dialog implemente su propio manejo. Va PRIMERO en el array de
 * withInterceptors (ver app.config.ts) a propósito: así es el ÚLTIMO en ver
 * la respuesta en el camino de vuelta, después de que apiErrorInterceptor ya
 * normalizó el error a ApiError y authTokenInterceptor ya agotó su intento de
 * refresh silencioso - nunca muestra el modal para un TOKEN_EXPIRED que se
 * termina resolviendo solo.
 */
export const globalErrorInterceptor: HttpInterceptorFn = (req, next) => {
  const dialog = inject(MatDialog);
  const skip = SKIP_PATH_FRAGMENTS.some((fragment) => req.url.includes(fragment));

  return next(req).pipe(
    catchError((error: unknown) => {
      if (!skip && isApiError(error)) {
        dialog.open(ConfirmDialog, {
          width: '420px',
          data: {
            title: 'Ocurrió un problema',
            message: formErrorMessage(error) ?? 'Ocurrió un error inesperado. Probá de nuevo.',
            tone: 'danger',
            icon: 'error',
          },
        });
      }

      return throwError(() => error);
    }),
  );
};
