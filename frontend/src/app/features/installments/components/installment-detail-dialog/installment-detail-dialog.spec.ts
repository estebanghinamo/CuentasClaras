import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { InstallmentDto } from '../../../../core/models/installment.models';
import { InstallmentDetailDialog, InstallmentDetailDialogData } from './installment-detail-dialog';

describe('InstallmentDetailDialog', () => {
  let component: InstallmentDetailDialog;
  let fixture: ComponentFixture<InstallmentDetailDialog>;

  const installment: InstallmentDto = {
    id: 1,
    user_id: 1,
    user_name: 'Juan',
    description: 'Notebook',
    category_id: null,
    category_name: null,
    total_amount: 1200,
    installments_count: 12,
    installment_amount: 100,
    start_date: '2026-01-01',
    status: 'active',
    paid_count: 1,
    remaining_count: 11,
    remaining_amount: 1100,
    next_due: { year: 2026, month: 2 },
    can_edit: true,
    // Desordenados a propósito: el backend no garantiza el orden, esto
    // prueba que el componente los reordena por número al armar el signal.
    payments: [
      { id: 3, number: 3, year: 2026, month: 3, amount: 100, status: 'pending', paid_at: null },
      { id: 1, number: 1, year: 2026, month: 1, amount: 100, status: 'paid', paid_at: '2026-01-05' },
      { id: 2, number: 2, year: 2026, month: 2, amount: 100, status: 'pending', paid_at: null },
    ],
  };

  const dialogData: InstallmentDetailDialogData = { workspaceId: 1, installment };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [InstallmentDetailDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(InstallmentDetailDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('sorts payments by installment number regardless of backend order', () => {
    expect(component.payments().map((p) => p.number)).toEqual([1, 2, 3]);
  });

  it('finds the first unpaid installment number and only allows paying that one', () => {
    expect(component.firstUnpaidNumber()).toBe(2);
    expect(component.canPay(component.payments()[1])).toBe(true);
    expect(component.canPay(component.payments()[2])).toBe(false);
  });
});
