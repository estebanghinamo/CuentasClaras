import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { forkJoin, map } from 'rxjs';
import { BudgetSummaryDto } from '../../core/models/budget.models';
import { CategoryDto } from '../../core/models/category.models';
import { CategoriesService } from '../categories/categories.service';
import { BudgetsResource } from './budgets.resource';

export interface BudgetsPageData {
  summary: BudgetSummaryDto;
  categories: CategoryDto[];
  year: number;
  month: number;
}

export const budgetsResolver: ResolveFn<BudgetsPageData> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));
  const now = new Date();
  const year = Number(route.queryParamMap.get('year')) || now.getFullYear();
  const month = Number(route.queryParamMap.get('month')) || now.getMonth() + 1;

  return forkJoin({
    summary: inject(BudgetsResource).list(workspaceId, year, month),
    categories: inject(CategoriesService).list(workspaceId),
  }).pipe(map(({ summary, categories }) => ({ summary, categories, year, month })));
};
