import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NotificationPreferencesDto } from '../../../../core/models/notification.models';
import { environment } from '../../../../../environments/environment';
import { NotificationPreferences } from './notification-preferences';

const PREFERENCES: NotificationPreferencesDto = {
  service_reminder_days: [3, 1],
  budget_alert_levels: ['warning', 'reached', 'exceeded'],
  channels: { in_app: true, push: true, email: false },
  muted_types: [],
};

describe('NotificationPreferences', () => {
  let component: NotificationPreferences;
  let fixture: ComponentFixture<NotificationPreferences>;
  let httpMock: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NotificationPreferences],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(NotificationPreferences);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);
    fixture.detectChanges();

    httpMock.expectOne(`${environment.apiUrl}/notifications/preferences`).flush({ success: true, data: PREFERENCES });
    fixture.detectChanges();
  });

  afterEach(() => httpMock.verify());

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('loads the preferences into the form', () => {
    expect(component.reminderDays()).toEqual([3, 1]);
    expect(component.isBudgetAlertLevelChecked('warning')).toBe(true);
    expect(component.form.controls.channels.value.email).toBe(false);
  });

  it('adds and removes reminder days without duplicates', () => {
    component.newDayControl.setValue(7);
    component.addReminderDay();
    expect(component.reminderDays()).toEqual([3, 1, 7]);

    component.newDayControl.setValue(7);
    component.addReminderDay();
    expect(component.reminderDays()).toEqual([3, 1, 7]);

    component.removeReminderDay(3);
    expect(component.reminderDays()).toEqual([1, 7]);
  });

  it('toggles muted types', () => {
    expect(component.isTypeMuted('smart_suggestion')).toBe(false);

    component.toggleMutedType('smart_suggestion', true);
    expect(component.isTypeMuted('smart_suggestion')).toBe(true);

    component.toggleMutedType('smart_suggestion', false);
    expect(component.isTypeMuted('smart_suggestion')).toBe(false);
  });
});
