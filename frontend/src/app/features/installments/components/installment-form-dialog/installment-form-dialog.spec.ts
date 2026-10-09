import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { InstallmentFormDialog, InstallmentFormDialogData } from './installment-form-dialog';

describe('InstallmentFormDialog', () => {
  let component: InstallmentFormDialog;
  let fixture: ComponentFixture<InstallmentFormDialog>;

  const dialogData: InstallmentFormDialogData = { workspaceId: 1, installment: null, categories: [] };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [InstallmentFormDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(InstallmentFormDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
