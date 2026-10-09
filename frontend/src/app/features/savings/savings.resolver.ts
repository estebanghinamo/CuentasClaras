import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { forkJoin } from 'rxjs';
import { Paginated } from '../../core/models/api.models';
import { SavingsMovementDto, SavingsWalletDto, SavingsWalletHistoryPoint } from '../../core/models/savings.models';
import { SavingsResource } from './savings.resource';

export interface SavingsPageData {
  wallet: SavingsWalletDto;
  movements: Paginated<SavingsMovementDto>;
  history: SavingsWalletHistoryPoint[];
}

export const savingsResolver: ResolveFn<SavingsPageData> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));
  const resource = inject(SavingsResource);

  return forkJoin({
    wallet: resource.getWallet(workspaceId),
    movements: resource.listMovements(workspaceId, 1, 20),
    history: resource.getHistory(workspaceId, 12),
  });
};
