import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MovementFormDialog, MovementFormDialogData } from './movement-form-dialog';

describe('MovementFormDialog', () => {
  let component: MovementFormDialog;
  let fixture: ComponentFixture<MovementFormDialog>;

  const dialogData: MovementFormDialogData = { workspaceId: 1, type: 'deposit', currentBalance: 500 };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MovementFormDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(MovementFormDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
