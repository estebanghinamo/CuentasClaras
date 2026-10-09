import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ProfilePage } from './profile.page';

describe('ProfilePage', () => {
  let component: ProfilePage;
  let fixture: ComponentFixture<ProfilePage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProfilePage],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(ProfilePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('flags mismatched password confirmation', () => {
    component.passwordForm.setValue({
      current_password: 'oldpass1',
      password: 'newpass1',
      password_confirmation: 'different1',
    });

    expect(component.passwordForm.hasError('passwordMismatch')).toBeTrue();
  });

  it('toggles the theme and reflects it in isDarkMode', () => {
    expect(component.isDarkMode()).toBeFalse();

    component.toggleTheme(true);
    expect(component.isDarkMode()).toBeTrue();

    component.toggleTheme(false);
    expect(component.isDarkMode()).toBeFalse();
  });
});
