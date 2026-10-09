import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { SessionService } from '../services/session.service';

/** Rutas públicas de auth (login, registro, etc.): si ya hay sesión, redirige. */
export const guestGuard: CanActivateFn = () => {
  const session = inject(SessionService);
  const router = inject(Router);

  if (session.accessToken()) {
    return router.createUrlTree(['/']);
  }

  return true;
};
