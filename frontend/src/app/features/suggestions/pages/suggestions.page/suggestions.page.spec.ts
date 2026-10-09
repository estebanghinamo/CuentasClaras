import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap } from '@angular/router';
import { SmartSuggestionDto } from '../../../../core/models/suggestion.models';
import { SuggestionsPage } from './suggestions.page';

const SUGGESTION: SmartSuggestionDto = {
  id: 1,
  description: 'Netflix',
  avg_amount: 5500,
  frequency_detected: 'monthly',
  occurrences: 4,
  last_seen_date: '2026-09-05',
  suggested_due_day: 5,
  status: 'pending',
  service_id: null,
  created_at: '2026-09-05',
};

describe('SuggestionsPage', () => {
  let component: SuggestionsPage;
  let fixture: ComponentFixture<SuggestionsPage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SuggestionsPage],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: ActivatedRoute, useValue: { snapshot: { paramMap: convertToParamMap({ workspaceId: '1' }) } } },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(SuggestionsPage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('suggestionsData', [SUGGESTION]);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('reflects the input data as the suggestions signal', () => {
    expect(component.suggestions()).toEqual([SUGGESTION]);
  });
});
