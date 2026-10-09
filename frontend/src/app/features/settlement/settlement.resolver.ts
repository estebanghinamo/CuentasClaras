import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { SettlementSummaryDto } from '../../core/models/settlement.models';
import { SettlementResource } from './settlement.resource';

export const settlementResolver: ResolveFn<SettlementSummaryDto> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));

  return inject(SettlementResource).getSummary(workspaceId);
};
