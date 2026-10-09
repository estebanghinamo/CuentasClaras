import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { WorkspaceContextService } from '../services/workspace-context.service';

/** Se aplica sobre /w/:workspaceId (path vacío), después de workspaceGuard:
 * un workspace 'shared_settlement' no tiene sección "Dashboard" (ver
 * workspace-shell.component.ts, SETTLEMENT_NAV_ITEMS) - redirigir ahí como
 * a los demás tipos rompía el panel de navegación. */
export const workspaceHomeGuard: CanActivateFn = (route) => {
  const context = inject(WorkspaceContextService);
  const router = inject(Router);

  const workspaceId = route.paramMap.get('workspaceId') ?? route.parent?.paramMap.get('workspaceId');
  const home = context.membership()?.type === 'shared_settlement' ? 'settlement' : 'dashboard';

  return router.createUrlTree([`/w/${workspaceId}/${home}`]);
};
