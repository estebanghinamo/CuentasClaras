import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DayOfMonthPicker } from './day-of-month-picker';

describe('DayOfMonthPicker', () => {
  let component: DayOfMonthPicker;
  let fixture: ComponentFixture<DayOfMonthPicker>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DayOfMonthPicker],
    }).compileComponents();

    fixture = TestBed.createComponent(DayOfMonthPicker);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('label', 'Día de vencimiento');
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('lists the 31 days of a month', () => {
    expect(component.days).toEqual(Array.from({ length: 31 }, (_, i) => i + 1));
  });

  it('dayLabel is empty when no day is selected', () => {
    expect(component.dayLabel()).toBe('');
  });

  it('dayLabel formats the selected day', () => {
    fixture.componentRef.setInput('day', 15);
    fixture.detectChanges();
    expect(component.dayLabel()).toBe('Día 15');
  });

  it('dayLabel formats day 1 and day 31 (boundary values)', () => {
    fixture.componentRef.setInput('day', 1);
    fixture.detectChanges();
    expect(component.dayLabel()).toBe('Día 1');

    fixture.componentRef.setInput('day', 31);
    fixture.detectChanges();
    expect(component.dayLabel()).toBe('Día 31');
  });

  it('dayLabel goes back to empty when day is cleared to null', () => {
    fixture.componentRef.setInput('day', 20);
    fixture.detectChanges();
    expect(component.dayLabel()).toBe('Día 20');

    fixture.componentRef.setInput('day', null);
    fixture.detectChanges();
    expect(component.dayLabel()).toBe('');
  });

  it('emits dayChange when a day is selected', () => {
    const emitted: (number | null)[] = [];
    component.dayChange.subscribe((day) => emitted.push(day));

    component.selectDay(10);
    component.selectDay(null);

    expect(emitted).toEqual([10, null]);
  });

  it('emits dayChange once per selectDay call, with the exact value passed', () => {
    const emitted: (number | null)[] = [];
    component.dayChange.subscribe((day) => emitted.push(day));

    component.selectDay(1);
    component.selectDay(31);

    expect(emitted).toHaveSize(2);
    expect(emitted[0]).toBe(1);
    expect(emitted[1]).toBe(31);
  });

  it('allowClear defaults to false', () => {
    expect(component.allowClear()).toBe(false);
  });

  it('allowClear reflects the input value when set to true', () => {
    fixture.componentRef.setInput('allowClear', true);
    fixture.detectChanges();
    expect(component.allowClear()).toBe(true);
  });

  it('day defaults to null when not provided', () => {
    expect(component.day()).toBeNull();
  });

  it('errorMessage defaults to null when not provided', () => {
    expect(component.errorMessage()).toBeNull();
  });

  it('errorMessage reflects the input value when set', () => {
    fixture.componentRef.setInput('errorMessage', 'Campo requerido');
    fixture.detectChanges();
    expect(component.errorMessage()).toBe('Campo requerido');
  });

  it('label reflects the required input value', () => {
    expect(component.label()).toBe('Día de vencimiento');

    fixture.componentRef.setInput('label', 'Día de vencimiento (desde)');
    fixture.detectChanges();
    expect(component.label()).toBe('Día de vencimiento (desde)');
  });

  it('renders the mat-form-field label text in the DOM', () => {
    const labelEl: HTMLElement = fixture.nativeElement.querySelector('mat-label');
    expect(labelEl.textContent?.trim()).toBe('Día de vencimiento');
  });

  it('renders the readonly input with the current dayLabel as its value', () => {
    fixture.componentRef.setInput('day', 5);
    fixture.detectChanges();
    const input: HTMLInputElement = fixture.nativeElement.querySelector('input');
    expect(input.value).toBe('Día 5');
    expect(input.readOnly).toBe(true);
  });
});
