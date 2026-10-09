export type WorkspaceType = 'individual' | 'shared_joint' | 'shared_separate' | 'shared_settlement';
export type WorkspaceRole = 'owner' | 'member';

export interface WorkspaceDto {
  id: number;
  name: string;
  currency: string;
  type: WorkspaceType;
  created_by: number;
  role: WorkspaceRole;
  members_count: number;
  onboarding_completed: boolean;
  created_at: string;
}

export interface CreateWorkspaceRequest {
  name: string;
  currency: string;
  type: WorkspaceType;
}

export type UpdateWorkspaceRequest = CreateWorkspaceRequest;

export interface WorkspaceMemberDto {
  user_id: number;
  name: string;
  email: string;
  role: WorkspaceRole;
  joined_at: string | null;
}

export interface CreateInvitationRequest {
  email?: string | null;
  expires_in_days?: number;
}

export type InvitationStatus = 'pending' | 'accepted' | 'expired' | 'revoked';

export interface InvitationDto {
  id: number;
  code: string;
  link: string;
  email: string | null;
  status: InvitationStatus;
  expires_at: string;
  created_at: string;
  created_by_name: string;
}

export interface InvitationPreviewDto {
  code: string;
  workspace_name: string;
  workspace_type: WorkspaceType;
  invited_by_name: string;
  email_restricted: boolean;
  expires_at: string;
}
