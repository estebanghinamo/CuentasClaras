import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { AuthLayout } from '../../../../shared/components/auth-layout/auth-layout';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { AuthService } from '../../../../core/services/auth.service';
import { fieldError, formErrorMessage } from '../../../../shared/utils/api-error.util';

@Component({
  selector: 'app-register-page',
  imports: [
    ReactiveFormsModule,
    RouterLink,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    AuthLayout,
    Spinner,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './register.page.html',
})
export class RegisterPage {
  private readonly fb = inject(FormBuilder);
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  private readonly returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');

  readonly loading = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly bannerMessage = () => formErrorMessage(this.apiError());
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);
  readonly showPassword = signal(false);
  readonly showPasswordConfirmation = signal(false);

  readonly form = this.fb.nonNullable.group(
    {
      name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(150)]],
      email: ['', [Validators.required, Validators.email]],
      password: [
        '',
        [
          Validators.required,
          Validators.minLength(8),
          Validators.maxLength(72),
          // Igual que el backend: al menos una letra y un número (Password::min(8)->letters()->numbers()).
          Validators.pattern(/^(?=.*[A-Za-z])(?=.*\d).+$/),
        ],
      ],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: [passwordsMatch] },
  );

  submit(): void {
    if (this.form.invalid || this.loading()) {
      return;
    }

    this.loading.set(true);
    this.apiError.set(null);

    this.authService.register(this.form.getRawValue()).subscribe({
      next: () => this.router.navigateByUrl(this.returnUrl ?? '/'),
      error: (error: ApiError) => {
        this.loading.set(false);
        this.apiError.set(error);
      },
    });
  }
}

function passwordsMatch(group: AbstractControl): Record<string, boolean> | null {
  const password = group.get('password')?.value;
  const confirmation = group.get('password_confirmation')?.value;

  return password && confirmation && password !== confirmation ? { passwordMismatch: true } : null;
}
