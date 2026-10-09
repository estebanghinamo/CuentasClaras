import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideNativeDateAdapter } from '@angular/material/core';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { IncomeEntryDto, IncomeListDto } from '../../../../core/models/income.models';
import { IncomePage } from './income.page';

describe('IncomePage', () => {
  let component: IncomePage;
  let fixture: ComponentFixture<IncomePage>;

  const summary: IncomeListDto = { entries: [], total: 0, by_user: [] };

  const CLOSING_ENTRY: IncomeEntryDto = {
    id: 1,
    user_id: 1,
    user_name: 'Esteban',
    amount: 889462,
    concept: 'Sobrante del cierre 08/2026',
    date: '2026-09-01',
    year: 2026,
    month: 9,
    source: 'closing',
    created_at: '2026-09-01T00:00:00Z',
    can_edit: true,
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [IncomePage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideNativeDateAdapter(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(IncomePage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('summary', summary);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('opens the informational dialog for closing-allocation entries without throwing', () => {
    fixture.componentRef.setInput('summary', { entries: [CLOSING_ENTRY], total: 889462, by_user: [] });
    fixture.detectChanges();

    expect(() => component.showClosingAllocationInfo()).not.toThrow();
  });
});
