import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { map, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { WorkspaceContextService } from '../services/workspace-context.service';

/** Sobre /w/:workspaceId: carga el workspace y lo setea como activo. 404 -> /workspaces
 * (no revela si el workspace existe pero no sos miembro; ver ESPECIFICACION_TECNICA.md M-02). */
export const workspaceGuard: CanActivateFn = (route) => {
  const context = inject(WorkspaceContextService);
  const router = inject(Router);

  const workspaceId = Number(route.paramMap.get('workspaceId'));

  if (!Number.isInteger(workspaceId) || workspaceId <= 0) {
    return router.createUrlTree(['/workspaces']);
  }

  return context.loadWorkspace(workspaceId).pipe(
    map(() => true),
    catchError(() => of(router.createUrlTree(['/workspaces']))),
  );
};
