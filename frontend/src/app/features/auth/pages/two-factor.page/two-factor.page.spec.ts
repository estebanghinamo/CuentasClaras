import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { TwoFactorPage } from './two-factor.page';

describe('TwoFactorPage', () => {
  let component: TwoFactorPage;
  let fixture: ComponentFixture<TwoFactorPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TwoFactorPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
        { provide: ActivatedRoute, useValue: { snapshot: { queryParamMap: convertToParamMap({ challenge: 'tok' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(TwoFactorPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
