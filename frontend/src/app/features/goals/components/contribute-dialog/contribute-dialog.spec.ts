import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { SavingsGoalDto } from '../../../../core/models/savings-goal.models';
import { ContributeDialog, ContributeDialogData } from './contribute-dialog';

describe('ContributeDialog', () => {
  let component: ContributeDialog;
  let fixture: ComponentFixture<ContributeDialog>;

  const goal: SavingsGoalDto = {
    id: 1,
    name: 'Vacaciones',
    target_amount: 1000,
    current_amount: 200,
    due_date: null,
    status: 'active',
    progress_pct: 20,
    remaining_amount: 800,
    days_left: null,
    suggested_monthly: null,
    completed_at: null,
    created_at: '2026-01-01T00:00:00Z',
  };

  const dialogData: ContributeDialogData = { workspaceId: 1, goal, mode: 'contribution', walletBalance: 500 };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ContributeDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(ContributeDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
