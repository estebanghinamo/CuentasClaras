import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { WorkspaceDto } from '../../../core/models/workspace.models';
import { WorkspaceCard } from './workspace-card';

describe('WorkspaceCard', () => {
  let component: WorkspaceCard;
  let fixture: ComponentFixture<WorkspaceCard>;

  const workspace: WorkspaceDto = {
    id: 1,
    name: 'Mi espacio',
    currency: 'ARS',
    type: 'shared_joint',
    created_by: 1,
    role: 'owner',
    members_count: 3,
    onboarding_completed: true,
    created_at: '2026-01-01',
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WorkspaceCard],
      providers: [provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(WorkspaceCard);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('workspace', workspace);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('maps each workspace type to its label and icon', () => {
    expect(component.typeLabel('individual')).toBe('Individual');
    expect(component.typeLabel('shared_settlement')).toBe('Gastos compartidos');
    expect(component.typeIcon('shared_joint')).toBe('diversity_3');
  });

  it('only offers invite when showInvite is on, the role is owner and the type is not individual', () => {
    fixture.componentRef.setInput('showInvite', false);
    fixture.detectChanges();
    expect(component.canInvite()).toBe(false);

    fixture.componentRef.setInput('showInvite', true);
    fixture.detectChanges();
    expect(component.canInvite()).toBe(true);

    fixture.componentRef.setInput('workspace', { ...workspace, type: 'individual' });
    fixture.detectChanges();
    expect(component.canInvite()).toBe(false);

    fixture.componentRef.setInput('workspace', { ...workspace, type: 'shared_joint', role: 'member' });
    fixture.detectChanges();
    expect(component.canInvite()).toBe(false);
  });

  it('emits invite and stops the routerLink navigation from firing', () => {
    fixture.componentRef.setInput('showInvite', true);
    fixture.detectChanges();

    const emitted = jasmine.createSpy('invite');
    component.invite.subscribe(emitted);

    const event = new MouseEvent('click');
    spyOn(event, 'preventDefault');
    spyOn(event, 'stopPropagation');

    component.onInvite(event);

    expect(event.preventDefault).toHaveBeenCalled();
    expect(event.stopPropagation).toHaveBeenCalled();
    expect(emitted).toHaveBeenCalledWith(event);
  });
});
