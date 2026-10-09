import { Injectable, inject } from '@angular/core';
import { Observable, shareReplay, tap } from 'rxjs';
import { CategoryDto, CreateCategoryRequest, UpdateCategoryRequest } from '../../core/models/category.models';
import { CategoriesResource } from './categories.resource';

/**
 * Caché por workspace (ver ESPECIFICACION_TECNICA.md M-04 §4): las categorías
 * se piden mucho (formularios de gasto, filtros, etc.) y cambian poco, así que
 * se cachea un Observable compartido por workspace y se invalida solo tras
 * una mutación. No hace falta invalidar al cambiar de workspace (la clave ya
 * es por id).
 */
@Injectable({ providedIn: 'root' })
export class CategoriesService {
  private readonly resource = inject(CategoriesResource);
  private readonly cache = new Map<number, Observable<CategoryDto[]>>();

  list(workspaceId: number): Observable<CategoryDto[]> {
    let cached = this.cache.get(workspaceId);
    if (!cached) {
      cached = this.resource.list(workspaceId).pipe(shareReplay(1));
      this.cache.set(workspaceId, cached);
    }
    return cached;
  }

  create(workspaceId: number, request: CreateCategoryRequest): Observable<CategoryDto> {
    return this.resource.create(workspaceId, request).pipe(tap(() => this.invalidate(workspaceId)));
  }

  update(workspaceId: number, categoryId: number, request: UpdateCategoryRequest): Observable<CategoryDto> {
    return this.resource.update(workspaceId, categoryId, request).pipe(tap(() => this.invalidate(workspaceId)));
  }

  delete(workspaceId: number, categoryId: number): Observable<void> {
    return this.resource.delete(workspaceId, categoryId).pipe(tap(() => this.invalidate(workspaceId)));
  }

  invalidate(workspaceId: number): void {
    this.cache.delete(workspaceId);
  }
}
