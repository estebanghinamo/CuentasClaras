import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatTooltipModule } from '@angular/material/tooltip';
import { NotificationBell } from '../../../shared/components/notification-bell/notification-bell';
import { AuthService } from '../../services/auth.service';

/**
 * Shell "general", fuera de cualquier workspace específico: /workspaces
 * (elegir/crear), /profile. Solo estas dos secciones - nada de Categorías,
 * Miembros, etc. acá, eso vive en workspace-shell.component.ts una vez que
 * el usuario entra a un workspace puntual (ver PROJECT_STATE.json).
 */
@Component({
  selector: 'app-general-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, MatButtonModule, MatTooltipModule, NotificationBell],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './general-shell.component.html',
})
export class GeneralShellComponent {
  protected readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  logout(): void {
    this.authService.logout().subscribe(() => this.router.navigateByUrl('/auth/login'));
  }

  userInitials(): string {
    const name = this.authService.currentUser()?.name ?? '';
    return name
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }
}
