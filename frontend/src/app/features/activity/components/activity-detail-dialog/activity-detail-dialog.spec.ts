import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';
import { AuditLogDto } from '../../../../core/models/activity.models';
import { ActivityDetailDialog, ActivityDetailDialogData } from './activity-detail-dialog';

describe('ActivityDetailDialog', () => {
  let component: ActivityDetailDialog;
  let fixture: ComponentFixture<ActivityDetailDialog>;

  const entry: AuditLogDto = {
    id: 1,
    user_id: 2,
    user_name: 'Juan Pérez',
    entity_type: 'expense',
    entity_id: 5,
    action: 'updated',
    summary: 'Editó un gasto',
    old_value: { amount: 100 },
    new_value: { amount: 150 },
    created_at: '2026-09-21T12:00:00Z',
  };

  const dialogData: ActivityDetailDialogData = { entry };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ActivityDetailDialog],
      providers: [{ provide: MAT_DIALOG_DATA, useValue: dialogData }],
    }).compileComponents();

    fixture = TestBed.createComponent(ActivityDetailDialog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
