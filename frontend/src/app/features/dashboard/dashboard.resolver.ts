import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { forkJoin } from 'rxjs';
import { BudgetSummaryDto } from '../../core/models/budget.models';
import { DashboardDto, HistoryPointDto } from '../../core/models/dashboard.models';
import { BudgetsResource } from '../budgets/budgets.resource';
import { DashboardResource } from './dashboard.resource';

export interface DashboardPageData {
  dashboard: DashboardDto;
  history: HistoryPointDto[];
  budgets: BudgetSummaryDto;
}

export const dashboardResolver: ResolveFn<DashboardPageData> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));
  const now = new Date();

  return forkJoin({
    dashboard: inject(DashboardResource).get(workspaceId),
    history: inject(DashboardResource).history(workspaceId, 12),
    budgets: inject(BudgetsResource).list(workspaceId, now.getFullYear(), now.getMonth() + 1),
  });
};
