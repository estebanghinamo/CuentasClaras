import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { IncomeListDto } from '../../core/models/income.models';
import { IncomeResource } from './income.resource';

/** Carga el mes actual al entrar; income.page.ts navega entre meses después con el mismo resource. */
export const incomeResolver: ResolveFn<IncomeListDto> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));
  const now = new Date();

  return inject(IncomeResource).list(workspaceId, now.getFullYear(), now.getMonth() + 1);
};
