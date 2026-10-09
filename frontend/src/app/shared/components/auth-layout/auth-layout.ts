import { ChangeDetectionStrategy, Component, input } from '@angular/core';

/**
 * Envoltorio visual compartido por todas las páginas de /auth (login,
 * registro, recuperar contraseña, 2FA, etc.) - centraliza el logo + título +
 * subtítulo para no repetirlo en cada página (antes cada una tenía solo un
 * <h1> de texto plano, sin marca).
 */
@Component({
  selector: 'app-auth-layout',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './auth-layout.html',
  styleUrl: './auth-layout.scss',
})
export class AuthLayout {
  readonly title = input.required<string>();
  readonly subtitle = input<string | null>(null);
}
