import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { SavingsPageData } from '../../savings.resolver';
import { SavingsPage } from './savings.page';

describe('SavingsPage', () => {
  let component: SavingsPage;
  let fixture: ComponentFixture<SavingsPage>;

  const pageData: SavingsPageData = {
    wallet: { id: 1, balance: 0, total_deposited: 0, total_withdrawn: 0, updated_at: '2026-01-01T00:00:00Z' },
    movements: { items: [], meta: { page: 1, per_page: 20, total: 0, last_page: 1 } },
    history: [],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SavingsPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(SavingsPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('savingsData', pageData);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
