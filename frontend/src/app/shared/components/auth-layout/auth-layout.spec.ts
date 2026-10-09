import { ComponentFixture, TestBed } from '@angular/core/testing';
import { By } from '@angular/platform-browser';
import { AuthLayout } from './auth-layout';

describe('AuthLayout', () => {
  let fixture: ComponentFixture<AuthLayout>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AuthLayout],
    }).compileComponents();

    fixture = TestBed.createComponent(AuthLayout);
    fixture.componentRef.setInput('title', 'Bienvenido de nuevo');
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('renders the title', () => {
    const heading: HTMLElement = fixture.debugElement.query(By.css('h1')).nativeElement;
    expect(heading.textContent).toContain('Bienvenido de nuevo');
  });

  it('does not render a subtitle when none is given', () => {
    expect(fixture.debugElement.query(By.css('.cc-caption'))).toBeNull();
  });

  it('renders the subtitle when given', () => {
    fixture.componentRef.setInput('subtitle', 'Ingresá para ver tus finanzas');
    fixture.detectChanges();

    const subtitle: HTMLElement = fixture.debugElement.query(By.css('.cc-caption')).nativeElement;
    expect(subtitle.textContent).toContain('Ingresá para ver tus finanzas');
  });
});
