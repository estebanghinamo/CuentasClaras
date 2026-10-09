import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { BudgetFormDialog, BudgetFormDialogData } from './budget-form-dialog';

describe('BudgetFormDialog', () => {
  let component: BudgetFormDialog;
  let fixture: ComponentFixture<BudgetFormDialog>;

  const dialogData: BudgetFormDialogData = {
    workspaceId: 1,
    year: 2026,
    month: 9,
    budget: null,
    categories: [],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BudgetFormDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(BudgetFormDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
