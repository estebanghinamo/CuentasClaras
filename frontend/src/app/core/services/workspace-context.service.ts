import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { WorkspaceDto, WorkspaceRole, WorkspaceType } from '../models/workspace.models';
import { WorkspaceResource } from '../resources/workspace.resource';

export interface ActiveMembership {
  role: WorkspaceRole;
  type: WorkspaceType;
  currency: string;
}

/**
 * Contexto del workspace activo. Se entra a un workspace clickeándolo en
 * /workspaces (workspace-shell.component.ts lo consume); no hay selector
 * global ni se restaura "el último" automáticamente - decisión explícita del
 * usuario para separar claramente lo general de lo específico de un workspace
 * (ver PROJECT_STATE.json). Todas las Resources de features construyen su URL
 * con activeWorkspaceId().
 */
@Injectable({ providedIn: 'root' })
export class WorkspaceContextService {
  private readonly resource = inject(WorkspaceResource);

  readonly workspaces = signal<WorkspaceDto[]>([]);
  readonly activeWorkspace = signal<WorkspaceDto | null>(null);

  readonly activeWorkspaceId = computed(() => this.activeWorkspace()?.id ?? null);
  readonly membership = computed<ActiveMembership | null>(() => {
    const w = this.activeWorkspace();
    return w ? { role: w.role, type: w.type, currency: w.currency } : null;
  });

  loadAll(): Observable<WorkspaceDto[]> {
    return this.resource.list().pipe(tap((workspaces) => this.workspaces.set(workspaces)));
  }

  /** Usado por workspaceGuard: trae el workspace puntual y lo marca como activo. */
  loadWorkspace(workspaceId: number): Observable<WorkspaceDto> {
    return this.resource.get(workspaceId).pipe(tap((workspace) => this.setActive(workspace)));
  }

  setActive(workspace: WorkspaceDto): void {
    this.activeWorkspace.set(workspace);
  }

  refreshActive(): Observable<WorkspaceDto> | null {
    const id = this.activeWorkspaceId();
    return id !== null ? this.loadWorkspace(id) : null;
  }

  clear(): void {
    this.workspaces.set([]);
    this.activeWorkspace.set(null);
  }
}
