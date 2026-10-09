import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { environment } from '../../../../../environments/environment';
import { ServiceDto } from '../../../../core/models/service.models';
import { SmartSuggestionDto } from '../../../../core/models/suggestion.models';
import { AcceptSuggestionDialog, AcceptSuggestionDialogData } from './accept-suggestion-dialog';

const suggestion: SmartSuggestionDto = {
  id: 1,
  description: 'Netflix',
  avg_amount: 5500,
  frequency_detected: 'monthly',
  occurrences: 4,
  last_seen_date: '2026-09-05',
  suggested_due_day: 5,
  status: 'pending',
  service_id: null,
  created_at: '2026-09-05',
};

const CREATED_SERVICE: ServiceDto = {
  id: 42,
  name: 'Netflix',
  amount: 5500,
  is_estimated: true,
  due_day_start: 5,
  due_day_end: null,
  late_fee_type: 'fixed',
  late_fee_value: 0,
  active: true,
  created_at: '2026-09-24',
};

describe('AcceptSuggestionDialog', () => {
  let component: AcceptSuggestionDialog;
  let fixture: ComponentFixture<AcceptSuggestionDialog>;
  let httpMock: HttpTestingController;
  let dialogRef: jasmine.SpyObj<MatDialogRef<AcceptSuggestionDialog>>;

  const dialogData: AcceptSuggestionDialogData = { workspaceId: 1, suggestion };

  beforeEach(async () => {
    dialogRef = jasmine.createSpyObj('MatDialogRef', ['close']);

    await TestBed.configureTestingModule({
      imports: [AcceptSuggestionDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: dialogRef },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(AcceptSuggestionDialog);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
  });

  afterEach(() => httpMock.verify());

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('prefills the form from the suggestion', () => {
    const raw = component.form.getRawValue();
    expect(raw.name).toBe('Netflix');
    expect(raw.amount).toBe(5500);
    expect(raw.due_day_start).toBe(5);
  });

  it('does not submit when the form is invalid', () => {
    component.form.controls.amount.setValue(null);
    component.submit();

    expect(component.saving()).toBe(false);
    httpMock.expectNone(() => true);
  });

  it('accepts the suggestion (POST .../accept) and closes the dialog on success', () => {
    component.submit();
    expect(component.saving()).toBe(true);

    const req = httpMock.expectOne(`${environment.apiUrl}/workspaces/1/suggestions/1/accept`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body.name).toBe('Netflix');

    req.flush({ success: true, data: { suggestion: { ...suggestion, status: 'accepted' }, service: CREATED_SERVICE } });

    expect(component.saving()).toBe(false);
    expect(dialogRef.close).toHaveBeenCalledWith(jasmine.objectContaining({ service: CREATED_SERVICE }));
  });

  it('sets apiError and stops saving on a failed request', () => {
    component.submit();

    const req = httpMock.expectOne(`${environment.apiUrl}/workspaces/1/suggestions/1/accept`);
    req.flush({ success: false, error: { code: 'VALIDATION_ERROR', message: 'Error' } }, { status: 422, statusText: 'Unprocessable Entity' });

    expect(component.saving()).toBe(false);
    expect(component.apiError()).toBeTruthy();
    expect(dialogRef.close).not.toHaveBeenCalled();
  });

  it('ignores repeated submit calls while already saving', () => {
    component.submit();
    component.submit();

    const requests = httpMock.match(`${environment.apiUrl}/workspaces/1/suggestions/1/accept`);
    expect(requests).toHaveSize(1);
  });
});
