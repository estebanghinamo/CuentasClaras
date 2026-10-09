import { Location } from '@angular/common';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { NotificationsPage } from './notifications.page';

describe('NotificationsPage', () => {
  let component: NotificationsPage;
  let fixture: ComponentFixture<NotificationsPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NotificationsPage],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(NotificationsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('maps each notification type to an icon, with a fallback for unknown types', () => {
    expect(component.typeIcon('month_closed')).toBe('event_available');
    expect(component.typeIcon('smart_suggestion')).toBe('lightbulb');
    expect(component.typeIcon('workspace_invitation')).toBe('mail');
  });

  it('goes back to the previous location instead of a fixed route', () => {
    const location = TestBed.inject(Location);
    spyOn(location, 'back');

    component.goBack();

    expect(location.back).toHaveBeenCalled();
  });
});
