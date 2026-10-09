import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { BudgetsPageData } from '../../budgets.resolver';
import { BudgetsPage } from './budgets.page';

describe('BudgetsPage', () => {
  let component: BudgetsPage;
  let fixture: ComponentFixture<BudgetsPage>;

  const pageData: BudgetsPageData = {
    summary: { budgets: [], total_limit: 0, total_spent: 0, categories_without_budget: [] },
    categories: [],
    year: 2026,
    month: 9,
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BudgetsPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(BudgetsPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('budgetsData', pageData);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
