import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../models/api.models';
import {
  CreateInvitationRequest,
  CreateWorkspaceRequest,
  InvitationDto,
  InvitationPreviewDto,
  UpdateWorkspaceRequest,
  WorkspaceDto,
  WorkspaceMemberDto,
} from '../models/workspace.models';

@Injectable({ providedIn: 'root' })
export class WorkspaceResource {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/workspaces`;
  private readonly invitationsBase = `${environment.apiUrl}/invitations`;

  list(): Observable<WorkspaceDto[]> {
    return this.http.get<ApiSuccess<WorkspaceDto[]>>(this.base).pipe(map((r) => r.data));
  }

  create(request: CreateWorkspaceRequest): Observable<WorkspaceDto> {
    return this.http.post<ApiSuccess<WorkspaceDto>>(this.base, request).pipe(map((r) => r.data));
  }

  get(workspaceId: number): Observable<WorkspaceDto> {
    return this.http.get<ApiSuccess<WorkspaceDto>>(`${this.base}/${workspaceId}`).pipe(map((r) => r.data));
  }

  update(workspaceId: number, request: UpdateWorkspaceRequest): Observable<WorkspaceDto> {
    return this.http.put<ApiSuccess<WorkspaceDto>>(`${this.base}/${workspaceId}`, request).pipe(map((r) => r.data));
  }

  delete(workspaceId: number): Observable<void> {
    return this.http.delete<void>(`${this.base}/${workspaceId}`);
  }

  listMembers(workspaceId: number): Observable<WorkspaceMemberDto[]> {
    return this.http
      .get<ApiSuccess<WorkspaceMemberDto[]>>(`${this.base}/${workspaceId}/members`)
      .pipe(map((r) => r.data));
  }

  removeMember(workspaceId: number, userId: number): Observable<void> {
    return this.http.delete<void>(`${this.base}/${workspaceId}/members/${userId}`);
  }

  leave(workspaceId: number): Observable<void> {
    return this.http.post<void>(`${this.base}/${workspaceId}/leave`, {});
  }

  completeOnboarding(workspaceId: number): Observable<WorkspaceDto> {
    return this.http
      .post<ApiSuccess<WorkspaceDto>>(`${this.base}/${workspaceId}/onboarding/complete`, {})
      .pipe(map((r) => r.data));
  }

  listInvitations(workspaceId: number): Observable<InvitationDto[]> {
    return this.http
      .get<ApiSuccess<InvitationDto[]>>(`${this.base}/${workspaceId}/invitations`)
      .pipe(map((r) => r.data));
  }

  createInvitation(workspaceId: number, request: CreateInvitationRequest): Observable<InvitationDto> {
    return this.http
      .post<ApiSuccess<InvitationDto>>(`${this.base}/${workspaceId}/invitations`, request)
      .pipe(map((r) => r.data));
  }

  revokeInvitation(workspaceId: number, invitationId: number): Observable<void> {
    return this.http.delete<void>(`${this.base}/${workspaceId}/invitations/${invitationId}`);
  }

  previewInvitation(code: string): Observable<InvitationPreviewDto> {
    return this.http
      .get<ApiSuccess<InvitationPreviewDto>>(`${this.invitationsBase}/${code}`)
      .pipe(map((r) => r.data));
  }

  acceptInvitation(code: string): Observable<WorkspaceDto> {
    return this.http
      .post<ApiSuccess<WorkspaceDto>>(`${this.invitationsBase}/${code}/accept`, {})
      .pipe(map((r) => r.data));
  }

  /** Invitaciones pendientes para el email del usuario logueado (alternativa a aceptar por mail). */
  listMyInvitations(): Observable<InvitationPreviewDto[]> {
    return this.http
      .get<ApiSuccess<InvitationPreviewDto[]>>(`${this.invitationsBase}/mine`)
      .pipe(map((r) => r.data));
  }
}
