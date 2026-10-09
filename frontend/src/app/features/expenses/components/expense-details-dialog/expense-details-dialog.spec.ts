import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';
import { ExpenseDto } from '../../../../core/models/expense.models';
import { ExpenseDetailsDialog, ExpenseDetailsDialogData } from './expense-details-dialog';

describe('ExpenseDetailsDialog', () => {
  let component: ExpenseDetailsDialog;
  let fixture: ComponentFixture<ExpenseDetailsDialog>;

  const expense: ExpenseDto = {
    id: 1,
    user_id: 1,
    user_name: 'Juan',
    category_id: 1,
    category_name: 'Comida',
    category_icon: 'restaurant',
    category_color: '#F44336',
    amount: 100,
    description: 'Almuerzo',
    payment_method: 'cash',
    date: '2026-09-01',
    created_at: '2026-09-01T12:00:00Z',
    can_edit: true,
  };

  const dialogData: ExpenseDetailsDialogData = { expense };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ExpenseDetailsDialog],
      providers: [provideHttpClient(), provideHttpClientTesting(), { provide: MAT_DIALOG_DATA, useValue: dialogData }],
    }).compileComponents();

    fixture = TestBed.createComponent(ExpenseDetailsDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
