import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { WorkspaceContextService } from '../services/workspace-context.service';

/** Se aplica después de workspaceGuard: requiere que el usuario sea owner del
 * workspace activo (settings, invitaciones). Si no, vuelve al workspace. */
export const workspaceOwnerGuard: CanActivateFn = (route) => {
  const context = inject(WorkspaceContextService);
  const router = inject(Router);

  if (context.membership()?.role === 'owner') {
    return true;
  }

  const workspaceId = route.paramMap.get('workspaceId') ?? route.parent?.paramMap.get('workspaceId');

  return router.createUrlTree([`/w/${workspaceId}`]);
};
