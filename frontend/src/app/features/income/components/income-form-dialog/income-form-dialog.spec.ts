import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { IncomeFormDialog, IncomeFormDialogData } from './income-form-dialog';

describe('IncomeFormDialog', () => {
  let component: IncomeFormDialog;
  let fixture: ComponentFixture<IncomeFormDialog>;

  const dialogData: IncomeFormDialogData = { workspaceId: 1, entry: null };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [IncomeFormDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(IncomeFormDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
