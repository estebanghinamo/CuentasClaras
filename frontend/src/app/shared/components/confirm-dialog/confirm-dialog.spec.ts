import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ConfirmDialog, ConfirmDialogData } from './confirm-dialog';

describe('ConfirmDialog', () => {
  let component: ConfirmDialog;
  let fixture: ComponentFixture<ConfirmDialog>;
  let dialogRefSpy: jasmine.SpyObj<MatDialogRef<ConfirmDialog, boolean>>;

  const data: ConfirmDialogData = {
    title: '¿Eliminar?',
    message: 'Esta acción no se puede deshacer.',
    confirmLabel: 'Eliminar',
    cancelLabel: 'Cancelar',
  };

  beforeEach(async () => {
    dialogRefSpy = jasmine.createSpyObj('MatDialogRef', ['close']);

    await TestBed.configureTestingModule({
      imports: [ConfirmDialog],
      providers: [
        { provide: MAT_DIALOG_DATA, useValue: data },
        { provide: MatDialogRef, useValue: dialogRefSpy },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(ConfirmDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('closes with true on confirm', () => {
    const buttons = fixture.nativeElement.querySelectorAll('button');
    const confirmButton: HTMLButtonElement = Array.from(buttons).find((b) =>
      (b as HTMLButtonElement).textContent?.includes('Eliminar'),
    ) as HTMLButtonElement;

    confirmButton.click();

    expect(dialogRefSpy.close).toHaveBeenCalledWith(true);
  });
});
