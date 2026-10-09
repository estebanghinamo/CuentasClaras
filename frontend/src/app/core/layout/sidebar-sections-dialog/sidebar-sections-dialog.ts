import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { Spinner } from '../../../shared/components/spinner/spinner';
import { ApiError } from '../../models/api.models';
import { OptionalSidebarSection } from '../../models/sidebar-section.models';
import { SidebarSectionsService } from '../../services/sidebar-sections.service';

export interface SidebarSectionsDialogData {
  workspaceId: number;
  enabled: OptionalSidebarSection[];
}

const AVAILABLE_SECTIONS: { code: OptionalSidebarSection; label: string; icon: string }[] = [
  { code: 'budgets', label: 'Presupuestos', icon: 'pie_chart' },
  { code: 'savings', label: 'Monedero', icon: 'savings' },
  { code: 'goals', label: 'Metas', icon: 'flag' },
  { code: 'members', label: 'Miembros', icon: 'group' },
  { code: 'activity', label: 'Actividad', icon: 'history' },
];

@Component({
  selector: 'app-sidebar-sections-dialog',
  imports: [MatDialogModule, MatButtonModule, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './sidebar-sections-dialog.html',
  styleUrl: './sidebar-sections-dialog.scss',
})
export class SidebarSectionsDialog {
  protected readonly data = inject<SidebarSectionsDialogData>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<SidebarSectionsDialog>);
  private readonly sidebarSections = inject(SidebarSectionsService);

  protected readonly sections = AVAILABLE_SECTIONS;

  readonly saving = signal(false);
  readonly apiError = signal<ApiError | null>(null);

  readonly selected = signal<Record<OptionalSidebarSection, boolean>>(
    Object.fromEntries(
      AVAILABLE_SECTIONS.map(({ code }) => [code, this.data.enabled.includes(code)]),
    ) as Record<OptionalSidebarSection, boolean>,
  );

  toggle(code: OptionalSidebarSection): void {
    this.selected.update((current) => ({ ...current, [code]: !current[code] }));
  }

  submit(): void {
    if (this.saving()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    const chosen = this.sections.map((s) => s.code).filter((code) => this.selected()[code]);

    this.sidebarSections.set(this.data.workspaceId, chosen).subscribe({
      next: () => {
        this.saving.set(false);
        this.dialogRef.close(true);
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }
}
