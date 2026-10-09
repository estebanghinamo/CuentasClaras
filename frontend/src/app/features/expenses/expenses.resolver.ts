import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { ExpenseListDto } from '../../core/models/expense.models';
import { ExpensesResource } from './expenses.resource';

function monthRange(): { date_from: string; date_to: string } {
  const now = new Date();
  const from = new Date(now.getFullYear(), now.getMonth(), 1);
  const to = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  const fmt = (d: Date) => d.toISOString().slice(0, 10);

  return { date_from: fmt(from), date_to: fmt(to) };
}

/** Carga el mes actual al entrar; expenses.page.ts filtra después con el mismo resource. */
export const expensesResolver: ResolveFn<ExpenseListDto> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));

  return inject(ExpensesResource).list(workspaceId, { ...monthRange(), page: 1, per_page: 20 });
};
