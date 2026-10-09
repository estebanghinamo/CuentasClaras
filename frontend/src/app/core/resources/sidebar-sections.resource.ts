import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../models/api.models';
import { SetSidebarSectionsRequest, SidebarSectionsDto } from '../models/sidebar-section.models';

@Injectable({ providedIn: 'root' })
export class SidebarSectionsResource {
  private readonly http = inject(HttpClient);

  private base(workspaceId: number): string {
    return `${environment.apiUrl}/workspaces/${workspaceId}/sidebar-sections`;
  }

  list(workspaceId: number): Observable<SidebarSectionsDto> {
    return this.http.get<ApiSuccess<SidebarSectionsDto>>(this.base(workspaceId)).pipe(map((r) => r.data));
  }

  set(workspaceId: number, request: SetSidebarSectionsRequest): Observable<SidebarSectionsDto> {
    return this.http.put<ApiSuccess<SidebarSectionsDto>>(this.base(workspaceId), request).pipe(map((r) => r.data));
  }
}
