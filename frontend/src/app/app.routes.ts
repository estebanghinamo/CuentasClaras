import { Routes } from '@angular/router';
import { connectionCheckResolver } from './connection-check/connection-check.resolver';
import { authGuard } from './core/guards/auth.guard';
import { workspaceGuard } from './core/guards/workspace.guard';
import { profileResolver } from './features/profile/profile.resolver';
import { workspacesResolver } from './features/workspaces/workspaces.resolver';

export const routes: Routes = [
  {
    path: 'status',
    resolve: { connection: connectionCheckResolver },
    loadComponent: () =>
      import('./connection-check/connection-check.page/connection-check.page').then((m) => m.ConnectionCheckPage),
  },
  {
    path: 'auth',
    loadChildren: () => import('./features/auth/auth.routes').then((m) => m.authRoutes),
  },
  {
    path: 'invite/:code',
    loadComponent: () => import('./features/workspaces/pages/invite-accept.page/invite-accept.page').then((m) => m.InviteAcceptPage),
  },
  // Shell general: fuera de cualquier workspace puntual (elegir/crear uno, perfil).
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () => import('./core/layout/general-shell.component/general-shell.component').then((m) => m.GeneralShellComponent),
    children: [
      {
        path: '',
        pathMatch: 'full',
        resolve: { workspaces: workspacesResolver },
        loadComponent: () => import('./features/home/home.page/home.page').then((m) => m.HomePage),
      },
      {
        path: 'profile',
        resolve: { user: profileResolver },
        loadComponent: () => import('./features/profile/pages/profile.page/profile.page').then((m) => m.ProfilePage),
      },
      {
        path: 'workspaces',
        loadChildren: () => import('./features/workspaces/workspaces.routes').then((m) => m.workspacesRoutes),
      },
      {
        path: 'notifications',
        loadComponent: () => import('./features/notifications/pages/notifications.page/notifications.page').then((m) => m.NotificationsPage),
      },
    ],
  },
  // Shell de un workspace específico: Categorías, Miembros, Actividad, Configuración.
  {
    path: 'w',
    canActivate: [authGuard],
    loadComponent: () => import('./core/layout/workspace-shell.component/workspace-shell.component').then((m) => m.WorkspaceShellComponent),
    children: [
      {
        path: ':workspaceId',
        canActivate: [workspaceGuard],
        loadChildren: () => import('./features/workspaces/workspaces.routes').then((m) => m.workspaceSectionRoutes),
      },
    ],
  },
];
