import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router, provideRouter } from '@angular/router';
import { environment } from '../../../../environments/environment';
import { InvitationPreviewDto, WorkspaceDto } from '../../../core/models/workspace.models';
import { WorkspaceContextService } from '../../../core/services/workspace-context.service';
import { HomePage } from './home.page';

describe('HomePage', () => {
  let component: HomePage;
  let fixture: ComponentFixture<HomePage>;
  let httpMock: HttpTestingController;

  const invitation: InvitationPreviewDto = {
    code: 'abc123',
    workspace_name: 'Mi espacio',
    workspace_type: 'shared_joint',
    invited_by_name: 'Ana',
    email_restricted: false,
    expires_at: '2026-12-31',
  };

  const workspace: WorkspaceDto = {
    id: 5,
    name: 'Mi espacio',
    currency: 'ARS',
    type: 'shared_joint',
    created_by: 1,
    role: 'member',
    members_count: 2,
    onboarding_completed: true,
    created_at: '2026-01-01',
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HomePage],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(HomePage);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);

    httpMock.expectOne(`${environment.apiUrl}/invitations/mine`).flush({ success: true, data: [invitation] });
    fixture.detectChanges();
  });

  afterEach(() => httpMock.verify());

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('loads the pending invitations on init', () => {
    expect(component.invitations()).toEqual([invitation]);
  });

  it('accepts an invitation, sets it as the active workspace and navigates there', () => {
    const router = TestBed.inject(Router);
    const context = TestBed.inject(WorkspaceContextService);
    spyOn(router, 'navigate');
    spyOn(context, 'setActive');

    component.accept(invitation);
    expect(component.accepting()).toBe('abc123');

    httpMock
      .expectOne(`${environment.apiUrl}/invitations/${invitation.code}/accept`)
      .flush({ success: true, data: workspace });

    expect(component.accepting()).toBeNull();
    expect(component.invitations()).toEqual([]);
    expect(context.setActive).toHaveBeenCalledWith(workspace);
    expect(router.navigate).toHaveBeenCalledWith(['/w', workspace.id]);
  });

  it('ignores a second accept() call while one is already in flight', () => {
    component.accept(invitation);
    component.accept(invitation);

    const requests = httpMock.match(`${environment.apiUrl}/invitations/${invitation.code}/accept`);
    expect(requests).toHaveSize(1);
    requests[0].flush({ success: true, data: workspace });
  });
});
