import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MonthlyClosingDto } from '../../../../core/models/closing.models';
import { SavingsGoalDto } from '../../../../core/models/savings-goal.models';
import { AllocateDialog, AllocateDialogData } from './allocate-dialog';

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
  health_score: null,
  breakdown: null,
  next_month_open: true,
};

const GOAL: SavingsGoalDto = {
  id: 1,
  name: 'Viaje',
  target_amount: 10000,
  current_amount: 1000,
  due_date: null,
  status: 'active',
  progress_pct: 10,
  remaining_amount: 9000,
  days_left: null,
  suggested_monthly: null,
  completed_at: null,
  created_at: '2026-01-01',
};

describe('AllocateDialog', () => {
  let component: AllocateDialog;
  let fixture: ComponentFixture<AllocateDialog>;

  function setup(data: AllocateDialogData): void {
    TestBed.configureTestingModule({
      imports: [AllocateDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: data },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AllocateDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  }

  it('should create', () => {
    setup({ workspaceId: 1, closing: CLOSING, goals: [GOAL] });
    expect(component).toBeTruthy();
  });

  it('flags exceedsAvailable when the requested total is over unallocated_amount', () => {
    setup({ workspaceId: 1, closing: CLOSING, goals: [] });

    component.form.controls.to_wallet.setValue(3500);
    expect(component.totalRequested()).toBe(3500);
    expect(component.exceedsAvailable()).toBe(true);
  });

  it('sums to_wallet + goals + to_next_month for totalRequested', () => {
    setup({ workspaceId: 1, closing: CLOSING, goals: [GOAL] });

    component.form.controls.to_wallet.setValue(1000);
    component.form.controls.goals.at(0).setValue(500);
    component.form.controls.to_next_month.setValue(200);

    expect(component.totalRequested()).toBe(1700);
    expect(component.exceedsAvailable()).toBe(false);
    expect(component.nothingSelected()).toBe(false);
  });

  it('disables to_next_month when the next month is already closed', () => {
    setup({ workspaceId: 1, closing: { ...CLOSING, next_month_open: false }, goals: [] });

    expect(component.form.controls.to_next_month.disabled).toBe(true);
  });
});
