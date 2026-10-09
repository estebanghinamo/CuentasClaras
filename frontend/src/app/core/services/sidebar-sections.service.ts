import { Injectable, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { OptionalSidebarSection, SidebarSectionsDto } from '../models/sidebar-section.models';
import { SidebarSectionsResource } from '../resources/sidebar-sections.resource';

/**
 * Estado reactivo de las secciones opcionales del sidebar del workspace activo.
 * WorkspaceShellComponent llama ensureLoaded() en un effect() sobre
 * context.activeWorkspaceId() (el shell se reutiliza al cambiar de workspace,
 * mismo patrón NG8118 que el resto del proyecto - ver PROJECT_STATE.json).
 */
@Injectable({ providedIn: 'root' })
export class SidebarSectionsService {
  private readonly resource = inject(SidebarSectionsResource);
  private readonly _enabled = signal<OptionalSidebarSection[]>([]);
  private loadedWorkspaceId: number | null = null;

  readonly enabled = this._enabled.asReadonly();

  ensureLoaded(workspaceId: number): void {
    if (this.loadedWorkspaceId === workspaceId) {
      return;
    }
    this.loadedWorkspaceId = workspaceId;

    this.resource.list(workspaceId).subscribe((dto) => {
      if (this.loadedWorkspaceId === workspaceId) {
        this._enabled.set(dto.sections);
      }
    });
  }

  set(workspaceId: number, sections: OptionalSidebarSection[]): Observable<SidebarSectionsDto> {
    return this.resource.set(workspaceId, { sections }).pipe(
      tap((dto) => {
        this.loadedWorkspaceId = workspaceId;
        this._enabled.set(dto.sections);
      }),
    );
  }
}
