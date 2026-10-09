import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { SettlementSummaryDto } from '../../../../core/models/settlement.models';
import { SettlementPage } from './settlement.page';

describe('SettlementPage', () => {
  let component: SettlementPage;
  let fixture: ComponentFixture<SettlementPage>;

  const summary: SettlementSummaryDto = {
    total_expenses: 0,
    member_count: 0,
    share_per_person: 0,
    balances: [],
    pending_settlements: [],
    settled_payments: [],
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SettlementPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(SettlementPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('settlementData', summary);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
