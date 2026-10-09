import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { SmartSuggestionDto } from '../../core/models/suggestion.models';
import { SuggestionsResource } from './suggestions.resource';

export const suggestionsResolver: ResolveFn<SmartSuggestionDto[]> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));

  return inject(SuggestionsResource).list(workspaceId, 'pending');
};
