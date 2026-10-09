import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { GoalsPageData } from '../../goals.resolver';
import { GoalsPage } from './goals.page';

describe('GoalsPage', () => {
  let component: GoalsPage;
  let fixture: ComponentFixture<GoalsPage>;

  const pageData: GoalsPageData = {
    goals: [],
    wallet: { id: 1, balance: 0, total_deposited: 0, total_withdrawn: 0, updated_at: '2026-01-01T00:00:00Z' },
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GoalsPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(GoalsPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('goalsData', pageData);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
