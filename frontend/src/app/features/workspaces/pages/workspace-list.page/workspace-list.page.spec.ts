import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MatDialog } from '@angular/material/dialog';
import { provideRouter } from '@angular/router';
import { WorkspaceDto } from '../../../../core/models/workspace.models';
import { WorkspaceListPage } from './workspace-list.page';

describe('WorkspaceListPage', () => {
  let component: WorkspaceListPage;
  let fixture: ComponentFixture<WorkspaceListPage>;

  const workspace: WorkspaceDto = {
    id: 1,
    name: 'Mi espacio',
    currency: 'ARS',
    type: 'shared_joint',
    created_by: 1,
    role: 'owner',
    members_count: 2,
    onboarding_completed: true,
    created_at: '2026-01-01',
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WorkspaceListPage],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(WorkspaceListPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('opens the quick invite dialog with the workspace id and name', () => {
    const dialog = TestBed.inject(MatDialog);
    spyOn(dialog, 'open').and.callThrough();

    component.openInvite(new MouseEvent('click'), workspace);

    expect(dialog.open).toHaveBeenCalledWith(
      jasmine.any(Function),
      jasmine.objectContaining({ data: { workspaceId: 1, workspaceName: 'Mi espacio' } }),
    );
  });
});
