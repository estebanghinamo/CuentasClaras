import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../../core/models/api.models';
import { ServiceDto } from '../../core/models/service.models';
import { AcceptSuggestionRequest, SmartSuggestionDto, SuggestionStatus } from '../../core/models/suggestion.models';

export interface AcceptSuggestionResult {
  suggestion: SmartSuggestionDto;
  service: ServiceDto;
}

@Injectable({ providedIn: 'root' })
export class SuggestionsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/suggestions`;
  }

  list(workspaceId: number, status: SuggestionStatus | 'all' = 'pending'): Observable<SmartSuggestionDto[]> {
    const params = new HttpParams().set('status', status);

    return this.http.get<ApiSuccess<SmartSuggestionDto[]>>(this.base(workspaceId), { params }).pipe(map((r) => r.data));
  }

  accept(workspaceId: number, suggestionId: number, request: AcceptSuggestionRequest): Observable<AcceptSuggestionResult> {
    return this.http
      .post<ApiSuccess<AcceptSuggestionResult>>(`${this.base(workspaceId)}/${suggestionId}/accept`, request)
      .pipe(map((r) => r.data));
  }

  dismiss(workspaceId: number, suggestionId: number): Observable<SmartSuggestionDto> {
    return this.http
      .post<ApiSuccess<SmartSuggestionDto>>(`${this.base(workspaceId)}/${suggestionId}/dismiss`, {})
      .pipe(map((r) => r.data));
  }
}
