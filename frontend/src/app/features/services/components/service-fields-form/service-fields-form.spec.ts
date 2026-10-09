import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FormBuilder } from '@angular/forms';
import { LateFeeType } from '../../../../core/models/service.models';
import { ServiceFieldsForm, ServiceFieldsFormGroup } from './service-fields-form';

describe('ServiceFieldsForm', () => {
  let component: ServiceFieldsForm;
  let fixture: ComponentFixture<ServiceFieldsForm>;
  let form: ServiceFieldsFormGroup;

  function buildForm(): ServiceFieldsFormGroup {
    const fb = new FormBuilder();
    return fb.group({
      name: fb.nonNullable.control(''),
      amount: fb.control<number | null>(null),
      is_estimated: fb.nonNullable.control(false),
      due_day_start: fb.control<number | null>(null),
      due_day_end: fb.control<number | null>(null),
      late_fee_type: fb.nonNullable.control<LateFeeType>('fixed'),
      late_fee_value: fb.control<number | null>(0),
    }) as unknown as ServiceFieldsFormGroup;
  }

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ServiceFieldsForm],
    }).compileComponents();

    fixture = TestBed.createComponent(ServiceFieldsForm);
    component = fixture.componentInstance;
    form = buildForm();
    fixture.componentRef.setInput('form', form);
    fixture.componentRef.setInput('fieldError', () => null);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('selectDay sets the value on the given form control', () => {
    component.selectDay('due_day_start', 12);
    expect(form.controls.due_day_start.value).toBe(12);

    component.selectDay('due_day_end', null);
    expect(form.controls.due_day_end.value).toBeNull();
  });

  it('binds the name input to the form control', () => {
    const input: HTMLInputElement = fixture.nativeElement.querySelector('input[formControlName], input');
    input.value = 'Netflix';
    input.dispatchEvent(new Event('input'));
    fixture.detectChanges();
    expect(form.controls.name.value).toBe('Netflix');
  });

  it('passes the field name through to the fieldError function for each field', () => {
    const seenFields: string[] = [];
    fixture.componentRef.setInput('fieldError', (field: string) => {
      seenFields.push(field);
      return null;
    });
    fixture.detectChanges();

    expect(seenFields).toContain('name');
    expect(seenFields).toContain('amount');
    expect(seenFields).toContain('due_day_start');
    expect(seenFields).toContain('due_day_end');
    expect(seenFields).toContain('late_fee_value');
  });
});
