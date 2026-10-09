import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { MonthlyClosingDto } from '../../core/models/closing.models';
import { ClosingsResource } from './closings.resource';

export const closingsResolver: ResolveFn<MonthlyClosingDto[]> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));

  return inject(ClosingsResource).list(workspaceId);
};
