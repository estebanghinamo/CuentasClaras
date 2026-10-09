import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';
import { ApiError } from '../models/api.models';

function isApiError(body: unknown): body is ApiError {
  return (
    typeof body === 'object' &&
    body !== null &&
    (body as { success?: unknown }).success === false &&
    'error' in body
  );
}

/**
 * Normaliza cualquier HttpErrorResponse al envelope ApiError (ver
 * ESPECIFICACION_TECNICA.md §0.4). Los componentes siempre reciben ApiError.
 */
export const apiErrorInterceptor: HttpInterceptorFn = (req, next) =>
  next(req).pipe(
    catchError((error: unknown) => {
      if (error instanceof HttpErrorResponse) {
        if (isApiError(error.error)) {
          return throwError(() => error.error as ApiError);
        }

        const networkError: ApiError = {
          success: false,
          error: {
            code: 'NETWORK_ERROR',
            message: 'No se pudo conectar con el servidor.',
            details: null,
          },
        };
        return throwError(() => networkError);
      }

      return throwError(() => error);
    }),
  );
