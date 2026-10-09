import { HttpBackend, HttpClient, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, switchMap, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiError } from '../models/api.models';
import { AuthTokensDto } from '../models/auth.models';
import { SessionService } from '../services/session.service';

/** apiErrorInterceptor corre más cerca del backend que este interceptor (ver
 * app.config.ts), así que para cuando este catchError ve el error, YA está
 * normalizado a ApiError (no queda HttpErrorResponse) - hay que leerlo en esa forma. */
function errorCode(error: unknown): string | undefined {
  return (error as Partial<ApiError> | null)?.error?.code;
}

const PUBLIC_PATH_FRAGMENTS = [
  '/auth/register',
  '/auth/login',
  '/auth/2fa/verify',
  '/auth/refresh',
  '/auth/forgot-password',
  '/auth/reset-password',
];

const INVITATIONS_SEGMENT = '/invitations/';
const WORKSPACE_PREFIX = /\/workspaces\/\d+$/;

/**
 * Solo el preview de una invitación TOP-LEVEL (GET /invitations/{code}) es público -
 * hace falta poder verlo sin sesión iniciada. /invitations/mine y /invitations/{code}/accept
 * requieren auth (ver routes/api.php), asi que quedan afuera exigiendo que el segmento
 * despues de /invitations/ sea el ultimo de la URL y no sea literalmente "mine". El chequeo
 * de prefijo excluye ademas /workspaces/{id}/invitations/{invitationId} (revocar invitacion,
 * tambien requiere auth), que por accidente comparte el mismo sufijo.
 *
 * Implementado sin lookaround (ni lookahead ni lookbehind): ambos pueden disparar
 * backtracking cuadrático en el motor de regex ante URLs largas (ver Sonar - reliability).
 */
function isPublicInvitationPreview(url: string): boolean {
  const idx = url.indexOf(INVITATIONS_SEGMENT);
  if (idx === -1) {
    return false;
  }

  const prefix = url.slice(0, idx);
  if (WORKSPACE_PREFIX.test(prefix)) {
    return false;
  }

  const rest = url.slice(idx + INVITATIONS_SEGMENT.length);
  const stopIdx = rest.search(/[/?]/);
  const segment = stopIdx === -1 ? rest : rest.slice(0, stopIdx);
  const isTopLevel = stopIdx === -1 || rest[stopIdx] === '?';

  return segment.length > 0 && isTopLevel && segment !== 'mine';
}

function isPublic(url: string): boolean {
  return PUBLIC_PATH_FRAGMENTS.some((fragment) => url.includes(fragment)) || isPublicInvitationPreview(url);
}

/**
 * Agrega el Bearer token a cada request (salvo rutas públicas). Ante un 401
 * (TOKEN_EXPIRED o UNAUTHENTICATED - ver nota abajo) hace un único refresh y
 * reintenta la request original; si el refresh falla, cierra la sesión y
 * manda a /auth/login (ver ESPECIFICACION_TECNICA.md M-01).
 */
export const authTokenInterceptor: HttpInterceptorFn = (req, next) => {
  const session = inject(SessionService);
  const httpBackend = inject(HttpBackend);
  const router = inject(Router);

  const publicRequest = isPublic(req.url);
  const token = publicRequest ? null : session.accessToken();
  const authReq = token ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : req;

  return next(authReq).pipe(
    catchError((error: unknown) => {
      // El backend solo puede devolver TOKEN_EXPIRED si la fila del access token
      // todavía existe en personal_access_tokens; si ya se borró (rotación de otro
      // refresh, limpieza, etc.) cae en UNAUTHENTICATED genérico - tratamos ambos
      // igual, porque en los dos casos lo correcto es intentar refrescar.
      const code = errorCode(error);
      const isAuthError = code === 'TOKEN_EXPIRED' || code === 'UNAUTHENTICATED';

      if (!isAuthError || publicRequest) {
        return throwError(() => error);
      }

      const refreshToken = session.refreshToken();
      if (!refreshToken) {
        session.clear();
        void router.navigateByUrl('/auth/login');
        return throwError(() => error);
      }

      // Se usa HttpBackend (no HttpClient inyectado normalmente) para saltear
      // este mismo interceptor y evitar un bucle infinito de refresh.
      const rawHttp = new HttpClient(httpBackend);

      return rawHttp
        .post<{ success: true; data: AuthTokensDto }>(`${environment.apiUrl}/auth/refresh`, {
          refresh_token: refreshToken,
        })
        .pipe(
          switchMap((response) => {
            session.setTokens(response.data.access_token, response.data.refresh_token);
            const retryReq = req.clone({
              setHeaders: { Authorization: `Bearer ${response.data.access_token}` },
            });
            return next(retryReq);
          }),
          catchError((refreshError: unknown) => {
            session.clear();
            void router.navigateByUrl('/auth/login');
            return throwError(() => refreshError);
          }),
        );
    }),
  );
};
