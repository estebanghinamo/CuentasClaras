import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { MatSelectModule } from '@angular/material/select';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { QRCodeComponent } from 'angularx-qrcode';
import { ApiError } from '../../../../core/models/api.models';
import { AuthResource } from '../../../../core/resources/auth.resource';
import { AuthService } from '../../../../core/services/auth.service';
import { ThemeService } from '../../../../core/services/theme.service';
import { ToastService } from '../../../../core/services/toast.service';
import { fieldError, formErrorMessage } from '../../../../shared/utils/api-error.util';
import { NotificationPreferences } from '../../components/notification-preferences/notification-preferences';

type TwoFactorStep = 'idle' | 'setup' | 'confirmed';

@Component({
  selector: 'app-profile-page',
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatSlideToggleModule,
    Spinner,
    QRCodeComponent,
    NotificationPreferences,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './profile.page.html',
})
export class ProfilePage {
  private readonly fb = inject(FormBuilder);
  private readonly authResource = inject(AuthResource);
  private readonly toast = inject(ToastService);
  private readonly themeService = inject(ThemeService);
  protected readonly authService = inject(AuthService);

  readonly isDarkMode = computed(() => this.themeService.current() === 'dark');

  readonly savingProfile = signal(false);
  readonly savingPassword = signal(false);
  readonly passwordApiError = signal<ApiError | null>(null);
  readonly passwordBannerMessage = () => formErrorMessage(this.passwordApiError());
  readonly passwordFieldError = (field: string) => fieldError(this.passwordApiError(), field);

  readonly twoFactorStep = signal<TwoFactorStep>('idle');
  readonly twoFactorBusy = signal(false);
  readonly twoFactorError = signal<string | null>(null);
  readonly otpauthUrl = signal('');
  readonly secret = signal('');
  readonly recoveryCodes = signal<string[]>([]);

  readonly profileForm = this.fb.nonNullable.group({
    name: [this.authService.currentUser()?.name ?? '', [Validators.required, Validators.minLength(2)]],
    locale: [(this.authService.currentUser()?.locale ?? 'es') as 'es' | 'en', [Validators.required]],
  });

  readonly passwordForm = this.fb.nonNullable.group(
    {
      current_password: ['', [Validators.required]],
      password: [
        '',
        [
          Validators.required,
          Validators.minLength(8),
          Validators.maxLength(72),
          Validators.pattern(/^(?=.*[A-Za-z])(?=.*\d).+$/),
        ],
      ],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: [passwordsMatch] },
  );

  readonly disableForm = this.fb.nonNullable.group({
    password: ['', [Validators.required]],
  });

  readonly confirmForm = this.fb.nonNullable.group({
    code: ['', [Validators.required, Validators.pattern(/^\d{6}$/)]],
  });

  saveProfile(): void {
    if (this.profileForm.invalid || this.savingProfile()) {
      return;
    }
    this.savingProfile.set(true);
    this.authService
      .updateProfile({ ...this.profileForm.getRawValue(), theme: this.themeService.current() })
      .subscribe({
        next: () => {
          this.savingProfile.set(false);
          this.toast.success('Perfil actualizado.');
        },
        error: (error: ApiError) => {
          this.savingProfile.set(false);
          this.toast.error(error.error?.message ?? 'No se pudo actualizar el perfil.');
        },
      });
  }

  toggleTheme(darkMode: boolean): void {
    const theme = darkMode ? 'dark' : 'light';
    this.themeService.apply(theme);
    this.authService
      .updateProfile({ ...this.profileForm.getRawValue(), theme })
      .subscribe({
        error: () => this.toast.error('No se pudo guardar la preferencia de tema.'),
      });
  }

  changePassword(): void {
    if (this.passwordForm.invalid || this.savingPassword()) {
      return;
    }
    this.savingPassword.set(true);
    this.passwordApiError.set(null);
    this.authResource.changePassword(this.passwordForm.getRawValue()).subscribe({
      next: () => {
        this.savingPassword.set(false);
        this.passwordForm.reset();
        this.toast.success('Contraseña actualizada.');
      },
      error: (error: ApiError) => {
        this.savingPassword.set(false);
        this.passwordApiError.set(error);
      },
    });
  }

  startEnable(): void {
    this.twoFactorBusy.set(true);
    this.twoFactorError.set(null);
    this.authResource.enableTwoFactor().subscribe({
      next: (setup) => {
        this.twoFactorBusy.set(false);
        this.otpauthUrl.set(setup.otpauth_url);
        this.secret.set(setup.secret);
        this.twoFactorStep.set('setup');
      },
      error: (error: ApiError) => {
        this.twoFactorBusy.set(false);
        this.twoFactorError.set(error.error?.message ?? 'No se pudo iniciar la configuración.');
      },
    });
  }

  confirmTwoFactor(): void {
    if (this.confirmForm.invalid || this.twoFactorBusy()) {
      return;
    }
    this.twoFactorBusy.set(true);
    this.twoFactorError.set(null);
    this.authResource.confirmTwoFactor(this.confirmForm.getRawValue()).subscribe({
      next: (result) => {
        this.twoFactorBusy.set(false);
        this.recoveryCodes.set(result.recovery_codes);
        this.twoFactorStep.set('confirmed');
      },
      error: (error: ApiError) => {
        this.twoFactorBusy.set(false);
        this.twoFactorError.set(error.error?.message ?? 'Código incorrecto.');
      },
    });
  }

  finishSetup(): void {
    this.twoFactorStep.set('idle');
    this.confirmForm.reset();
    this.authService.loadCurrentUser().subscribe();
  }

  disableTwoFactor(): void {
    if (this.disableForm.invalid || this.twoFactorBusy()) {
      return;
    }
    this.twoFactorBusy.set(true);
    this.twoFactorError.set(null);
    this.authResource.disableTwoFactor(this.disableForm.getRawValue()).subscribe({
      next: () => {
        this.twoFactorBusy.set(false);
        this.disableForm.reset();
        this.toast.success('2FA desactivado.');
        this.authService.loadCurrentUser().subscribe();
      },
      error: (error: ApiError) => {
        this.twoFactorBusy.set(false);
        this.twoFactorError.set(error.error?.message ?? 'No se pudo desactivar el 2FA.');
      },
    });
  }
}

function passwordsMatch(group: AbstractControl): Record<string, boolean> | null {
  const password = group.get('password')?.value;
  const confirmation = group.get('password_confirmation')?.value;

  return password && confirmation && password !== confirmation ? { passwordMismatch: true } : null;
}
