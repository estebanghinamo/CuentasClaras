import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { MonthlyClosingDto } from '../../../../core/models/closing.models';
import { ClosingsPage } from './closings.page';

const CLOSING: MonthlyClosingDto = {
  id: 1,
  year: 2026,
  month: 6,
  total_income: 5000,
  total_expenses: 2000,
  total_services: 0,
  total_installments: 0,
  remaining_amount: 3000,
  savings_generated: 0,
  allocated_to_wallet: 0,
  allocated_to_goals: 0,
  allocated_to_next_month: 0,
  unallocated_amount: 3000,
  allocation_status: 'pending',
  allocated_at: null,
  closed_by: 'Esteban',
  closed_at: '2026-07-01',
  health_score: 61,
  breakdown: null,
  next_month_open: true,
};

describe('ClosingsPage', () => {
  let component: ClosingsPage;
  let fixture: ComponentFixture<ClosingsPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ClosingsPage],
      providers: [{ provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } }],
    }).compileComponents();

    fixture = TestBed.createComponent(ClosingsPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('closingsData', [CLOSING]);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('builds the month label from year/month', () => {
    expect(component.monthLabel(CLOSING)).toBe('Junio 2026');
  });
});
