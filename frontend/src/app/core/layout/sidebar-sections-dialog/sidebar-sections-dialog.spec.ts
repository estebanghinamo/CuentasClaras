import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { SidebarSectionsDialog, SidebarSectionsDialogData } from './sidebar-sections-dialog';

describe('SidebarSectionsDialog', () => {
  let component: SidebarSectionsDialog;
  let fixture: ComponentFixture<SidebarSectionsDialog>;

  const data: SidebarSectionsDialogData = { workspaceId: 1, enabled: ['budgets'] };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SidebarSectionsDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: MAT_DIALOG_DATA, useValue: data },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(SidebarSectionsDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('marks the already-enabled section as selected', () => {
    expect(component.selected()['budgets']).toBeTrue();
    expect(component.selected()['goals']).toBeFalse();
  });

  it('toggles a section on click', () => {
    component.toggle('goals');
    expect(component.selected()['goals']).toBeTrue();
  });
});
