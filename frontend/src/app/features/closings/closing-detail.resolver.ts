import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { forkJoin } from 'rxjs';
import { MonthlyClosingDto } from '../../core/models/closing.models';
import { SavingsGoalDto } from '../../core/models/savings-goal.models';
import { GoalsResource } from '../goals/goals.resource';
import { ClosingsResource } from './closings.resource';

export interface ClosingDetailPageData {
  closing: MonthlyClosingDto;
  activeGoals: SavingsGoalDto[];
}

export const closingDetailResolver: ResolveFn<ClosingDetailPageData> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));
  const year = Number(route.paramMap.get('year'));
  const month = Number(route.paramMap.get('month'));

  return forkJoin({
    closing: inject(ClosingsResource).get(workspaceId, year, month),
    activeGoals: inject(GoalsResource).list(workspaceId, 'active'),
  });
};
