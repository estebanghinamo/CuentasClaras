import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { MatSelectModule } from '@angular/material/select';
import { ApiError } from '../../../../core/models/api.models';
import { WorkspaceType } from '../../../../core/models/workspace.models';
import { WorkspaceResource } from '../../../../core/resources/workspace.resource';
import { SidebarSectionsService } from '../../../../core/services/sidebar-sections.service';
import { WorkspaceContextService } from '../../../../core/services/workspace-context.service';
import { fieldError } from '../../../../shared/utils/api-error.util';

const CURRENCIES = ['ARS', 'USD', 'EUR', 'BRL', 'CLP', 'UYU', 'MXN', 'COP', 'PEN'];

const TYPE_OPTIONS: { value: WorkspaceType; label: string; hint: string }[] = [
  { value: 'individual', label: 'Individual', hint: 'Solo vos. No admite invitaciones.' },
  { value: 'shared_joint', label: 'Compartido conjunto', hint: 'Todos los miembros ven y editan todo.' },
  { value: 'shared_separate', label: 'Compartido separado', hint: 'Cada miembro edita solo sus propios registros.' },
  { value: 'shared_settlement', label: 'Gastos compartidos', hint: 'Para viajes o eventos: cada quien carga lo que pagó y el sistema calcula quién le debe a quién.' },
];

@Component({
  selector: 'app-workspace-create-page',
  imports: [
    ReactiveFormsModule,
    RouterLink,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    Spinner,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './workspace-create.page.html',
})
export class WorkspaceCreatePage {
  private readonly fb = inject(FormBuilder);
  private readonly workspaces = inject(WorkspaceResource);
  private readonly context = inject(WorkspaceContextService);
  private readonly sidebarSections = inject(SidebarSectionsService);
  private readonly router = inject(Router);

  protected readonly currencies = CURRENCIES;
  protected readonly typeOptions = TYPE_OPTIONS;

  readonly loading = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(150)]],
    currency: ['ARS', [Validators.required]],
    type: ['shared_joint' as WorkspaceType, [Validators.required]],
  });

  selectedTypeHint(): string {
    return this.typeOptions.find((o) => o.value === this.form.controls.type.value)?.hint ?? '';
  }

  submit(): void {
    if (this.form.invalid || this.loading()) {
      return;
    }

    this.loading.set(true);
    this.apiError.set(null);

    this.workspaces.create(this.form.getRawValue()).subscribe({
      next: (workspace) => {
        this.context.setActive(workspace);
        // Pendiente (M-20): navegar a /w/{id}/onboarding cuando exista; por ahora entra
        // directo al workspace recién creado (categorías, su "home"), salvo
        // "Gastos compartidos" que entra directo a su pantalla de Liquidación.
        if (workspace.type === 'shared_settlement') {
          this.router.navigate(['/w', workspace.id, 'settlement']);
        } else if (workspace.type === 'individual') {
          this.router.navigate(['/w', workspace.id]);
        } else {
          // Compartido (conjunto/separado): lo primero que se hace con un
          // workspace nuevo es invitar gente, y el Dashboard no tiene ninguna
          // forma de hacerlo - antes había que volver a /workspaces para
          // encontrar el botón "Invitar miembros". Se habilita "Miembros" en
          // el sidebar (antes quedaba escondido detrás de "Agregar sección")
          // y se entra directo ahí en vez del Dashboard.
          this.sidebarSections.set(workspace.id, ['members']).subscribe(() => {
            this.router.navigate(['/w', workspace.id, 'members']);
          });
        }
      },
      error: (error: ApiError) => {
        this.loading.set(false);
        this.apiError.set(error);
      },
    });
  }
}
