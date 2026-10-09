import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { WorkspaceDto } from '../../../../core/models/workspace.models';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { QuickInviteDialog, QuickInviteDialogData } from '../../components/quick-invite-dialog/quick-invite-dialog';
import { WorkspaceCard } from '../../../../shared/components/workspace-card/workspace-card';

@Component({
  selector: 'app-workspace-list-page',
  imports: [RouterLink, MatButtonModule, WorkspaceCard],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './workspace-list.page.html',
})
export class WorkspaceListPage {
  protected readonly context = inject(WorkspaceContextService);
  private readonly dialog = inject(MatDialog);

  openInvite(event: Event, workspace: WorkspaceDto): void {
    this.dialog.open<QuickInviteDialog, QuickInviteDialogData, boolean>(QuickInviteDialog, {
      width: '480px',
      data: { workspaceId: workspace.id, workspaceName: workspace.name },
    });
  }
}
