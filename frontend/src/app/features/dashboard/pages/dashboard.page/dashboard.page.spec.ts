import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideNativeDateAdapter } from '@angular/material/core';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { BudgetSummaryDto } from '../../../../core/models/budget.models';
import { DashboardDto } from '../../../../core/models/dashboard.models';
import { DashboardPageData } from '../../dashboard.resolver';
import { DashboardPage } from './dashboard.page';

describe('DashboardPage', () => {
  let component: DashboardPage;
  let fixture: ComponentFixture<DashboardPage>;

  const dashboard: DashboardDto = {
    year: 2026,
    month: 9,
    currency: 'ARS',
    totals: {
      income: 0,
      expenses: 0,
      category_expenses: 0,
      services_paid: 0,
      installments: 0,
      installments_paid: 0,
      committed: 0,
      available: 0,
      savings_deposited: 0,
      savings_withdrawn: 0,
      goals_contributed: 0,
      goals_withdrawn: 0,
    },
    by_category: Array.from({ length: 7 }, (_, i) => ({
      category_id: i + 1,
      category_name: `Categoría ${i + 1}`,
      category_color: '#000',
      category_icon: 'category',
      amount: 100,
      pct: 10,
    })),
    paid_services: [],
    installment_breakdown: [],
    by_user: [],
    comparison: null,
    overdue_services: { count: 0, items: [] },
    health: null,
    pending_allocation: null,
    projection: null,
  };

  const budgets: BudgetSummaryDto = {
    budgets: Array.from({ length: 7 }, (_, i) => ({
      id: i + 1,
      category_id: i + 1,
      category_name: `Categoría ${i + 1}`,
      category_icon: 'category',
      category_color: '#000',
      year: 2026,
      month: 9,
      limit_amount: 100,
      spent_amount: 50,
      remaining_amount: 50,
      progress_pct: 50,
      alert_level: 'none' as const,
    })),
    total_limit: 700,
    total_spent: 350,
    categories_without_budget: [],
  };

  const pageData: DashboardPageData = { dashboard, history: [], budgets };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DashboardPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideNativeDateAdapter(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DashboardPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('dashboardData', pageData);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('caps categories and budgets to 5 rows until expanded', () => {
    expect(component.visibleCategories()).toHaveSize(5);
    expect(component.visibleBudgets()).toHaveSize(5);

    component.categoriesExpanded.set(true);
    component.budgetsExpanded.set(true);

    expect(component.visibleCategories()).toHaveSize(7);
    expect(component.visibleBudgets()).toHaveSize(7);
  });
});
