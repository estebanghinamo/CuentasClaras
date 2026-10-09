import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { AuthLayout } from '../../../../shared/components/auth-layout/auth-layout';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { InvitationPreviewDto, WorkspaceType } from '../../../../core/models/workspace.models';
import { WorkspaceResource } from '../../../../core/resources/workspace.resource';
import { SessionService } from '../../../../core/services/session.service';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { WORKSPACE_TYPE_LABELS } from '../../../../shared/utils/workspace-type.util';

@Component({
  selector: 'app-invite-accept-page',
  imports: [RouterLink, MatButtonModule, AuthLayout, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './invite-accept.page.html',
})
export class InviteAcceptPage {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly workspaces = inject(WorkspaceResource);
  private readonly session = inject(SessionService);
  private readonly context = inject(WorkspaceContextService);

  private readonly code = this.route.snapshot.paramMap.get('code') ?? '';

  readonly loading = signal(true);
  readonly accepting = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly preview = signal<InvitationPreviewDto | null>(null);

  constructor() {
    this.workspaces.previewInvitation(this.code).subscribe({
      next: (preview) => {
        this.loading.set(false);
        this.preview.set(preview);
      },
      error: (error: ApiError) => {
        this.loading.set(false);
        this.errorMessage.set(error.error?.message ?? 'La invitación no es válida o ya venció.');
      },
    });
  }

  hasSession(): boolean {
    return this.session.accessToken() !== null;
  }

  returnUrlParams(): { returnUrl: string } {
    return { returnUrl: `/invite/${this.code}` };
  }

  typeLabel(type: WorkspaceType): string {
    return WORKSPACE_TYPE_LABELS[type];
  }

  accept(): void {
    if (this.accepting()) {
      return;
    }
    this.accepting.set(true);

    this.workspaces.acceptInvitation(this.code).subscribe({
      next: (workspace) => {
        this.accepting.set(false);
        this.context.setActive(workspace);
        this.router.navigate(['/w', workspace.id]);
      },
      error: (error: ApiError) => {
        this.accepting.set(false);
        this.errorMessage.set(error.error?.message ?? 'No se pudo aceptar la invitación.');
      },
    });
  }
}
