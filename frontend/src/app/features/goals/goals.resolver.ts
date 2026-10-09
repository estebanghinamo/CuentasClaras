import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { forkJoin } from 'rxjs';
import { SavingsGoalDto, SavingsGoalStatus } from '../../core/models/savings-goal.models';
import { SavingsWalletDto } from '../../core/models/savings.models';
import { SavingsResource } from '../savings/savings.resource';
import { GoalsResource } from './goals.resource';

export interface GoalsPageData {
  goals: SavingsGoalDto[];
  wallet: SavingsWalletDto;
}

export const goalsResolver: ResolveFn<GoalsPageData> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));
  const status = (route.queryParamMap.get('status') as SavingsGoalStatus | 'all' | null) ?? 'active';

  return forkJoin({
    goals: inject(GoalsResource).list(workspaceId, status),
    wallet: inject(SavingsResource).getWallet(workspaceId),
  });
};
