import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { WorkspaceDto } from '../../core/models/workspace.models';
import { WorkspaceContextService } from '../../core/services/workspace-context.service';

export const workspacesResolver: ResolveFn<WorkspaceDto[]> = () => inject(WorkspaceContextService).loadAll();
