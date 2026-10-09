import { Routes } from '@angular/router';
import { workspaceHomeGuard } from '../../core/guards/workspace-home.guard';
import { workspaceOwnerGuard } from '../../core/guards/workspace-owner.guard';
import { budgetsResolver } from '../budgets/budgets.resolver';
import { closingDetailResolver } from '../closings/closing-detail.resolver';
import { closingsResolver } from '../closings/closings.resolver';
import { dashboardResolver } from '../dashboard/dashboard.resolver';
import { expensesResolver } from '../expenses/expenses.resolver';
import { incomeResolver } from '../income/income.resolver';
import { goalsResolver } from '../goals/goals.resolver';
import { installmentsResolver } from '../installments/installments.resolver';
import { savingsResolver } from '../savings/savings.resolver';
import { servicesResolver } from '../services/services.resolver';
import { settlementResolver } from '../settlement/settlement.resolver';
import { suggestionsResolver } from '../suggestions/suggestions.resolver';
import { workspacesResolver } from './workspaces.resolver';

/** Bajo el general-shell: elegir/crear workspace, sin contexto de ninguno en particular. */
export const workspacesRoutes: Routes = [
  {
    path: '',
    resolve: { workspaces: workspacesResolver },
    loadComponent: () => import('./pages/workspace-list.page/workspace-list.page').then((m) => m.WorkspaceListPage),
  },
  {
    path: 'create',
    loadComponent: () => import('./pages/workspace-create.page/workspace-create.page').then((m) => m.WorkspaceCreatePage),
  },
];

/** Bajo el workspace-shell, ya con :workspaceId resuelto por workspaceGuard (ver app.routes.ts). */
export const workspaceSectionRoutes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    canActivate: [workspaceHomeGuard],
    children: [],
  },
  {
    path: 'dashboard',
    resolve: { dashboardData: dashboardResolver },
    loadComponent: () => import('../dashboard/pages/dashboard.page/dashboard.page').then((m) => m.DashboardPage),
  },
  {
    path: 'income',
    resolve: { summary: incomeResolver },
    loadComponent: () => import('../income/pages/income.page/income.page').then((m) => m.IncomePage),
  },
  {
    path: 'expenses',
    resolve: { summary: expensesResolver },
    loadComponent: () => import('../expenses/pages/expenses.page/expenses.page').then((m) => m.ExpensesPage),
  },
  {
    path: 'services',
    resolve: { servicesData: servicesResolver },
    loadComponent: () => import('../services/pages/services.page/services.page').then((m) => m.ServicesPage),
  },
  {
    path: 'installments',
    resolve: { installmentsData: installmentsResolver },
    loadComponent: () => import('../installments/pages/installments.page/installments.page').then((m) => m.InstallmentsPage),
  },
  {
    path: 'savings',
    resolve: { savingsData: savingsResolver },
    loadComponent: () => import('../savings/pages/savings.page/savings.page').then((m) => m.SavingsPage),
  },
  {
    path: 'goals',
    resolve: { goalsData: goalsResolver },
    loadComponent: () => import('../goals/pages/goals.page/goals.page').then((m) => m.GoalsPage),
  },
  {
    path: 'budgets',
    resolve: { budgetsData: budgetsResolver },
    loadComponent: () => import('../budgets/pages/budgets.page/budgets.page').then((m) => m.BudgetsPage),
  },
  {
    path: 'settlement',
    resolve: { settlementData: settlementResolver },
    loadComponent: () => import('../settlement/pages/settlement.page/settlement.page').then((m) => m.SettlementPage),
  },
  {
    path: 'closings',
    resolve: { closingsData: closingsResolver },
    loadComponent: () => import('../closings/pages/closings.page/closings.page').then((m) => m.ClosingsPage),
  },
  {
    path: 'closings/:year/:month',
    resolve: { closingData: closingDetailResolver },
    loadComponent: () => import('../closings/pages/closing-detail.page/closing-detail.page').then((m) => m.ClosingDetailPage),
  },
  {
    path: 'suggestions',
    resolve: { suggestionsData: suggestionsResolver },
    loadComponent: () => import('../suggestions/pages/suggestions.page/suggestions.page').then((m) => m.SuggestionsPage),
  },
  {
    path: 'members',
    loadComponent: () => import('./pages/members.page/members.page').then((m) => m.MembersPage),
  },
  {
    path: 'activity',
    loadComponent: () => import('../activity/pages/activity.page/activity.page').then((m) => m.ActivityPage),
  },
  {
    path: 'settings',
    canActivate: [workspaceOwnerGuard],
    loadComponent: () => import('./pages/workspace-settings.page/workspace-settings.page').then((m) => m.WorkspaceSettingsPage),
  },
];
