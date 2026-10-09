import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, ParamMap, Router, UrlTree, convertToParamMap, provideRouter } from '@angular/router';
import { WorkspaceDto } from '../models/workspace.models';
import { WorkspaceContextService } from '../services/workspace-context.service';
import { workspaceHomeGuard } from './workspace-home.guard';

describe('workspaceHomeGuard', () => {
  let context: WorkspaceContextService;
  let router: Router;

  const baseWorkspace: WorkspaceDto = {
    id: 7,
    name: 'Mi espacio',
    currency: 'ARS',
    type: 'individual',
    created_by: 1,
    role: 'owner',
    members_count: 1,
    onboarding_completed: true,
    created_at: '2026-01-01',
  };

  function routeWithWorkspaceId(id: string): ActivatedRouteSnapshot {
    const paramMap: ParamMap = convertToParamMap({ workspaceId: id });
    return { paramMap, parent: null } as unknown as ActivatedRouteSnapshot;
  }

  function runGuard(id: string): UrlTree {
    return TestBed.runInInjectionContext(() => workspaceHomeGuard(routeWithWorkspaceId(id), {} as never)) as UrlTree;
  }

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    });

    context = TestBed.inject(WorkspaceContextService);
    router = TestBed.inject(Router);
  });

  it('redirects to dashboard for a non-settlement workspace', () => {
    context.setActive({ ...baseWorkspace, type: 'individual' });

    const tree = runGuard('7');

    expect(router.serializeUrl(tree)).toBe('/w/7/dashboard');
  });

  it('redirects to settlement for a shared_settlement workspace', () => {
    context.setActive({ ...baseWorkspace, type: 'shared_settlement' });

    const tree = runGuard('7');

    expect(router.serializeUrl(tree)).toBe('/w/7/settlement');
  });
});
