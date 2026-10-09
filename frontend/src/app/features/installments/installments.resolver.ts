import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { forkJoin } from 'rxjs';
import { CategoryDto } from '../../core/models/category.models';
import { InstallmentDto } from '../../core/models/installment.models';
import { CategoriesService } from '../categories/categories.service';
import { InstallmentsResource } from './installments.resource';

export interface InstallmentsPageData {
  installments: InstallmentDto[];
  categories: CategoryDto[];
}

export const installmentsResolver: ResolveFn<InstallmentsPageData> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));

  return forkJoin({
    installments: inject(InstallmentsResource).list(workspaceId, 'all'),
    categories: inject(CategoriesService).list(workspaceId),
  });
};
