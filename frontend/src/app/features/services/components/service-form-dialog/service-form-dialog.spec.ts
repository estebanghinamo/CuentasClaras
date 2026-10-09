import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { environment } from '../../../../../environments/environment';
import { ServiceDto } from '../../../../core/models/service.models';
import { ServiceFormDialog, ServiceFormDialogData } from './service-form-dialog';

const EXISTING_SERVICE: ServiceDto = {
  id: 7,
  name: 'Netflix',
  amount: 5500,
  is_estimated: false,
  due_day_start: 10,
  due_day_end: null,
  late_fee_type: 'fixed',
  late_fee_value: 0,
  active: true,
  created_at: '2026-01-01',
};

describe('ServiceFormDialog', () => {
  let httpMock: HttpTestingController;
  let dialogRef: jasmine.SpyObj<MatDialogRef<ServiceFormDialog>>;

  function setup(dialogData: ServiceFormDialogData): { component: ServiceFormDialog; fixture: ComponentFixture<ServiceFormDialog> } {
    dialogRef = jasmine.createSpyObj('MatDialogRef', ['close']);

    TestBed.configureTestingModule({
      imports: [ServiceFormDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: dialogRef },
      ],
    });

    const fixture = TestBed.createComponent(ServiceFormDialog);
    httpMock = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
    return { component: fixture.componentInstance, fixture };
  }

  afterEach(() => httpMock.verify());

  it('should create', () => {
    const { component } = setup({ workspaceId: 1, service: null });
    expect(component).toBeTruthy();
  });

  it('does not submit when the form is invalid', () => {
    const { component } = setup({ workspaceId: 1, service: null });
    component.form.controls.name.setValue('');

    component.submit();

    expect(component.saving()).toBe(false);
    httpMock.expectNone(() => true);
  });

  it('creates a service (POST) and closes the dialog on success', () => {
    const { component } = setup({ workspaceId: 1, service: null });
    component.form.setValue({
      name: 'Spotify',
      amount: 2000,
      is_estimated: false,
      due_day_start: 5,
      due_day_end: null,
      late_fee_type: 'fixed',
      late_fee_value: 0,
      active: true,
    });

    component.submit();
    expect(component.saving()).toBe(true);

    const req = httpMock.expectOne(`${environment.apiUrl}/workspaces/1/services`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body.name).toBe('Spotify');
    req.flush({ success: true, data: { ...EXISTING_SERVICE, id: 99, name: 'Spotify' } });

    expect(component.saving()).toBe(false);
    expect(dialogRef.close).toHaveBeenCalledWith(jasmine.objectContaining({ name: 'Spotify' }));
  });

  it('updates an existing service (PUT) prefilled from data.service', () => {
    const { component } = setup({ workspaceId: 1, service: EXISTING_SERVICE });
    expect(component.form.getRawValue().name).toBe('Netflix');

    component.submit();

    const req = httpMock.expectOne(`${environment.apiUrl}/workspaces/1/services/7`);
    expect(req.request.method).toBe('PUT');
    req.flush({ success: true, data: EXISTING_SERVICE });

    expect(dialogRef.close).toHaveBeenCalledWith(EXISTING_SERVICE);
  });

  it('sets apiError and stops saving on a failed request', () => {
    const { component } = setup({ workspaceId: 1, service: null });
    component.form.setValue({
      name: 'Spotify',
      amount: 2000,
      is_estimated: false,
      due_day_start: 5,
      due_day_end: null,
      late_fee_type: 'fixed',
      late_fee_value: 0,
      active: true,
    });

    component.submit();
    const req = httpMock.expectOne(`${environment.apiUrl}/workspaces/1/services`);
    req.flush({ success: false, error: { code: 'VALIDATION_ERROR', message: 'Error' } }, { status: 422, statusText: 'Unprocessable Entity' });

    expect(component.saving()).toBe(false);
    expect(component.apiError()).toBeTruthy();
    expect(dialogRef.close).not.toHaveBeenCalled();
  });

  it('ignores repeated submit calls while already saving', () => {
    const { component } = setup({ workspaceId: 1, service: null });
    component.form.setValue({
      name: 'Spotify',
      amount: 2000,
      is_estimated: false,
      due_day_start: 5,
      due_day_end: null,
      late_fee_type: 'fixed',
      late_fee_value: 0,
      active: true,
    });

    component.submit();
    component.submit();

    const requests = httpMock.match(`${environment.apiUrl}/workspaces/1/services`);
    expect(requests).toHaveSize(1);
  });
});
