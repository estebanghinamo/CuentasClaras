import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { InvitationDto, WorkspaceMemberDto } from '../../../../core/models/workspace.models';
import { WorkspaceResource } from '../../../../core/resources/workspace.resource';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { ToastService } from '../../../../core/services/toast.service';
import { fieldError } from '../../../../shared/utils/api-error.util';

const STATUS_LABELS: Record<InvitationDto['status'], string> = {
  pending: 'Pendiente',
  accepted: 'Aceptada',
  expired: 'Vencida',
  revoked: 'Revocada',
};

@Component({
  selector: 'app-members-page',
  imports: [ReactiveFormsModule, RouterLink, MatButtonModule, MatFormFieldModule, MatInputModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './members.page.html',
  styleUrl: './members.page.scss',
})
export class MembersPage {
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly workspaces = inject(WorkspaceResource);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);

  // 'members' tiene path propio, no hereda params del padre por default
  // (paramsInheritanceStrategy: 'emptyOnly') - hay que subir a .parent.
  protected readonly workspaceId = Number(
    this.route.snapshot.paramMap.get('workspaceId') ?? this.route.snapshot.parent?.paramMap.get('workspaceId'),
  );

  readonly members = signal<WorkspaceMemberDto[]>([]);
  readonly invitations = signal<InvitationDto[]>([]);
  readonly busy = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly inviteForm = this.fb.nonNullable.group({
    email: ['', [Validators.email]],
  });

  constructor() {
    this.reloadMembers();
    if (this.isOwner() && this.workspaceType() !== 'individual') {
      this.reloadInvitations();
    }
  }

  isOwner(): boolean {
    return this.context.membership()?.role === 'owner';
  }

  workspaceType(): string | undefined {
    return this.context.membership()?.type;
  }

  statusLabel(status: InvitationDto['status']): string {
    return STATUS_LABELS[status];
  }

  removeMember(member: WorkspaceMemberDto): void {
    this.busy.set(true);
    this.workspaces.removeMember(this.workspaceId, member.user_id).subscribe({
      next: () => {
        this.busy.set(false);
        this.toast.success(`${member.name} fue quitado del espacio.`);
        this.reloadMembers();
      },
      error: () => this.busy.set(false),
    });
  }

  leave(): void {
    this.busy.set(true);
    this.workspaces.leave(this.workspaceId).subscribe({
      next: () => {
        this.busy.set(false);
        this.toast.success('Saliste del espacio.');
        this.context.clear();
        this.router.navigateByUrl('/workspaces');
      },
      error: () => this.busy.set(false),
    });
  }

  createInvitation(): void {
    if (this.inviteForm.invalid || this.busy()) {
      return;
    }
    this.busy.set(true);
    this.apiError.set(null);

    const email = this.inviteForm.getRawValue().email || null;

    this.workspaces.createInvitation(this.workspaceId, { email }).subscribe({
      next: () => {
        this.busy.set(false);
        this.inviteForm.reset();
        this.toast.success('Invitación creada.');
        this.reloadInvitations();
      },
      error: (error: ApiError) => {
        this.busy.set(false);
        this.apiError.set(error);
      },
    });
  }

  revokeInvitation(invitation: InvitationDto): void {
    this.busy.set(true);
    this.workspaces.revokeInvitation(this.workspaceId, invitation.id).subscribe({
      next: () => {
        this.busy.set(false);
        this.toast.success('Invitación revocada.');
        this.reloadInvitations();
      },
      error: () => this.busy.set(false),
    });
  }

  copyLink(invitation: InvitationDto): void {
    navigator.clipboard
      ?.writeText(invitation.link)
      .then(() => this.toast.success('Link copiado.'))
      .catch(() => this.toast.error('No se pudo copiar el link.'));
  }

  private reloadMembers(): void {
    this.workspaces.listMembers(this.workspaceId).subscribe((members) => this.members.set(members));
  }

  private reloadInvitations(): void {
    this.workspaces.listInvitations(this.workspaceId).subscribe((invitations) => this.invitations.set(invitations));
  }
}
