import { ChangeDetectionStrategy, Component, input, output } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { WorkspaceDto, WorkspaceType } from '../../../core/models/workspace.models';
import { WORKSPACE_TYPE_ICONS, WORKSPACE_TYPE_LABELS } from '../../utils/workspace-type.util';

/**
 * Tarjeta de espacio (workspace) - usada tanto en Home como en la página de
 * Espacios (antes el mismo HTML estaba calcado en las dos, detectado por
 * SonarQube como duplicación real). showRole/showInvite controlan los 2
 * bloques que solo aparecen en la página de Espacios, no en Home.
 */
@Component({
  selector: 'app-workspace-card',
  imports: [RouterLink, MatButtonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './workspace-card.html',
})
export class WorkspaceCard {
  readonly workspace = input.required<WorkspaceDto>();
  readonly showRole = input(false);
  readonly showInvite = input(false);
  readonly invite = output<Event>();

  typeLabel(type: WorkspaceType): string {
    return WORKSPACE_TYPE_LABELS[type];
  }

  typeIcon(type: WorkspaceType): string {
    return WORKSPACE_TYPE_ICONS[type];
  }

  canInvite(): boolean {
    return this.showInvite() && this.workspace().role === 'owner' && this.workspace().type !== 'individual';
  }

  onInvite(event: Event): void {
    event.preventDefault();
    event.stopPropagation();
    this.invite.emit(event);
  }
}
