import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { MatSelectModule } from '@angular/material/select';
import { ApiError } from '../../../../core/models/api.models';
import { WorkspaceType } from '../../../../core/models/workspace.models';
import { WorkspaceResource } from '../../../../core/resources/workspace.resource';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { ToastService } from '../../../../core/services/toast.service';
import { fieldError } from '../../../../shared/utils/api-error.util';

const CURRENCIES = ['ARS', 'USD', 'EUR', 'BRL', 'CLP', 'UYU', 'MXN', 'COP', 'PEN'];

@Component({
  selector: 'app-workspace-settings-page',
  imports: [
    ReactiveFormsModule,
    FormsModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    Spinner,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './workspace-settings.page.html',
})
export class WorkspaceSettingsPage {
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly workspaces = inject(WorkspaceResource);
  private readonly toast = inject(ToastService);
  protected readonly context = inject(WorkspaceContextService);

  // 'settings' tiene path propio, no hereda params del padre por default
  // (paramsInheritanceStrategy: 'emptyOnly') - hay que subir a .parent.
  private readonly workspaceId = Number(
    this.route.snapshot.paramMap.get('workspaceId') ?? this.route.snapshot.parent?.paramMap.get('workspaceId'),
  );

  protected readonly currencies = CURRENCIES;
  confirmName = '';

  readonly saving = signal(false);
  readonly deleting = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.nonNullable.group({
    name: [this.context.activeWorkspace()?.name ?? '', [Validators.required, Validators.minLength(2), Validators.maxLength(150)]],
    currency: [this.context.activeWorkspace()?.currency ?? 'ARS', [Validators.required]],
    type: [(this.context.activeWorkspace()?.type ?? 'shared_joint') as WorkspaceType, [Validators.required]],
  });

  save(): void {
    if (this.form.invalid || this.saving()) {
      return;
    }
    this.saving.set(true);
    this.apiError.set(null);

    this.workspaces.update(this.workspaceId, this.form.getRawValue()).subscribe({
      next: (workspace) => {
        this.saving.set(false);
        this.context.setActive(workspace);
        this.toast.success('Workspace actualizado.');
      },
      error: (error: ApiError) => {
        this.saving.set(false);
        this.apiError.set(error);
      },
    });
  }

  delete(): void {
    if (this.confirmName !== this.context.activeWorkspace()?.name || this.deleting()) {
      return;
    }
    this.deleting.set(true);

    this.workspaces.delete(this.workspaceId).subscribe({
      next: () => {
        this.deleting.set(false);
        this.context.clear();
        this.toast.success('Workspace eliminado.');
        this.router.navigateByUrl('/workspaces');
      },
      error: () => this.deleting.set(false),
    });
  }
}
