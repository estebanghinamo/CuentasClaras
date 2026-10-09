import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { QuickInviteDialog, QuickInviteDialogData } from './quick-invite-dialog';

describe('QuickInviteDialog', () => {
  let component: QuickInviteDialog;
  let fixture: ComponentFixture<QuickInviteDialog>;

  const dialogData: QuickInviteDialogData = { workspaceId: 1, workspaceName: 'Casa' };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [QuickInviteDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(QuickInviteDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
