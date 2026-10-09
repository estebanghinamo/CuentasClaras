import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { InstallmentsPageData } from '../../installments.resolver';
import { InstallmentsPage } from './installments.page';

describe('InstallmentsPage', () => {
  let component: InstallmentsPage;
  let fixture: ComponentFixture<InstallmentsPage>;

  const pageData: InstallmentsPageData = { installments: [], categories: [] };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [InstallmentsPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(InstallmentsPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('installmentsData', pageData);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
