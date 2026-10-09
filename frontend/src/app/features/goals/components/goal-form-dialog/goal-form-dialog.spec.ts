import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideNativeDateAdapter } from '@angular/material/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { GoalFormDialog, GoalFormDialogData } from './goal-form-dialog';

describe('GoalFormDialog', () => {
  let component: GoalFormDialog;
  let fixture: ComponentFixture<GoalFormDialog>;

  const dialogData: GoalFormDialogData = { workspaceId: 1, goal: null };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GoalFormDialog],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideNativeDateAdapter(),
        { provide: MAT_DIALOG_DATA, useValue: dialogData },
        { provide: MatDialogRef, useValue: jasmine.createSpyObj('MatDialogRef', ['close']) },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(GoalFormDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
