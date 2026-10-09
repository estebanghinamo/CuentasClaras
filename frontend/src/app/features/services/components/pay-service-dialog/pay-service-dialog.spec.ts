import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ServicePaymentDto } from '../../../../core/models/service.models';
import { PayServiceDialog, PayServiceDialogData } from './pay-service-dialog';

describe('PayServiceDialog', () => {
  let component: PayServiceDialog;
  let fixture: ComponentFixture<PayServiceDialog>;

  const payment: ServicePaymentDto = {
    id: 1,
    service_id: 1,
    service_name: 'Luz',
    year: 2026,
    month: 9,
    expected_amount: 100,
    due_date_start: '2026-09-01',
    due_date_end: '2026-09-10',
    status: 'pending',
    amount_paid: null,
    late_fee_applied: 0,
    paid_at: null,
    was_late: false,
    paid_by_user_id: null,
    paid_by_name: null,
    notes: null,
    days_until_due: 5,
  };

  const dialogData: PayServiceDialogData = { workspaceId: 1, payment, lateFeeType: 'percentage', lateFeeValue: 10 };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PayServiceDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(PayServiceDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
