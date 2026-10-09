import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { InvitationPreviewDto, WorkspaceType } from '../../../core/models/workspace.models';
import { WorkspaceResource } from '../../../core/resources/workspace.resource';
import { WorkspaceContextService } from '../../../core/services/workspace-context.service';
import { ToastService } from '../../../core/services/toast.service';
import { AuthService } from '../../../core/services/auth.service';
import { WorkspaceCard } from '../../../shared/components/workspace-card/workspace-card';
import { WORKSPACE_TYPE_LABELS } from '../../../shared/utils/workspace-type.util';

/**
 * Página principal tras iniciar sesión. Todavía no hay un dashboard global (M-21,
 * el dashboard real es por espacio): muestra un saludo + los espacios del
 * usuario con acceso directo (antes solo un link genérico a "/workspaces",
 * a pedido del usuario: "no me gusta, habría que cambiarla"), acceso a
 * Perfil, y las invitaciones pendientes para poder aceptarlas sin depender
 * de que el email haya llegado.
 */
@Component({
  selector: 'app-home-page',
  imports: [MatButtonModule, RouterLink, WorkspaceCard],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './home.page.html',
})
export class HomePage {
  private readonly workspaces = inject(WorkspaceResource);
  protected readonly context = inject(WorkspaceContextService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly authService = inject(AuthService);

  readonly invitations = signal<InvitationPreviewDto[]>([]);
  readonly accepting = signal<string | null>(null);

  userName(): string {
    return this.authService.currentUser()?.name.split(' ')[0] ?? '';
  }

  constructor() {
    this.workspaces.listMyInvitations().subscribe((invitations) => this.invitations.set(invitations));
  }

  typeLabel(type: WorkspaceType): string {
    return WORKSPACE_TYPE_LABELS[type];
  }

  accept(invitation: InvitationPreviewDto): void {
    if (this.accepting()) {
      return;
    }
    this.accepting.set(invitation.code);

    this.workspaces.acceptInvitation(invitation.code).subscribe({
      next: (workspace) => {
        this.accepting.set(null);
        this.invitations.update((list) => list.filter((i) => i.code !== invitation.code));
        this.context.setActive(workspace);
        this.toast.success(`Te uniste a ${workspace.name}.`);
        this.router.navigate(['/w', workspace.id]);
      },
      error: () => this.accepting.set(null),
    });
  }
}
