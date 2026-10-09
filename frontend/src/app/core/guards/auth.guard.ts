import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of, tap } from 'rxjs';
import { AuthService } from '../services/auth.service';
import { PushService } from '../services/push.service';
import { SessionService } from '../services/session.service';

/**
 * Requiere sesión. Además de que haya un token guardado, valida el token
 * contra el backend (GET /auth/me) antes de dejar entrar: si está vencido,
 * la llamada pasa por authTokenInterceptor, que la refresca sola y reintenta
 * (sin que el usuario note nada); si el refresh también falla, redirige a
 * login. Esto es necesario porque la ruta '' del shell general (home.page.ts)
 * no dispara ninguna llamada a la API por sí sola - sin este chequeo, un
 * token vencido dejaba al usuario mirando esa pantalla en blanco para siempre
 * (ver ERROR_LOG.md). Si no hay sesión, guarda la URL pedida como returnUrl
 * (ver M-19 §5: deep link sin sesión → login → vuelve al deep link).
 */
export const authGuard: CanActivateFn = (_route, state) => {
  const session = inject(SessionService);
  const authService = inject(AuthService);
  const pushService = inject(PushService);
  const router = inject(Router);

  if (!session.accessToken()) {
    return router.createUrlTree(['/auth/login'], { queryParams: { returnUrl: state.url } });
  }

  return authService.loadCurrentUser().pipe(
    tap(() => void pushService.init()),
    map(() => true),
    catchError(() => {
      authService.logoutLocal();
      return of(router.createUrlTree(['/auth/login'], { queryParams: { returnUrl: state.url } }));
    }),
  );
};
