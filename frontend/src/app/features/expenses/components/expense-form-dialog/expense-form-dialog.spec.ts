import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ExpenseFormDialog, ExpenseFormDialogData } from './expense-form-dialog';

describe('ExpenseFormDialog', () => {
  let component: ExpenseFormDialog;
  let fixture: ComponentFixture<ExpenseFormDialog>;

  const dialogData: ExpenseFormDialogData = {
    workspaceId: 1,
    expense: null,
    categories: [],
    recentDescriptions: [],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ExpenseFormDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(ExpenseFormDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
