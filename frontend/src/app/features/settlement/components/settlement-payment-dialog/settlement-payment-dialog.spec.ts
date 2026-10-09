import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { SettlementPaymentDialog, SettlementPaymentDialogData } from './settlement-payment-dialog';

describe('SettlementPaymentDialog', () => {
  let component: SettlementPaymentDialog;
  let fixture: ComponentFixture<SettlementPaymentDialog>;

  const dialogData: SettlementPaymentDialogData = {
    workspaceId: 1,
    members: [
      { user_id: 1, user_name: 'Juan' },
      { user_id: 2, user_name: 'María' },
    ],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SettlementPaymentDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(SettlementPaymentDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
