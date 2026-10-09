import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { AuthLayout } from '../../../../shared/components/auth-layout/auth-layout';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { AuthResource } from '../../../../core/resources/auth.resource';
import { fieldError, formErrorMessage } from '../../../../shared/utils/api-error.util';

@Component({
  selector: 'app-reset-password-page',
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
  templateUrl: './reset-password.page.html',
})
export class ResetPasswordPage {
  private readonly fb = inject(FormBuilder);
  private readonly authResource = inject(AuthResource);
  private readonly route = inject(ActivatedRoute);

  private readonly email = this.route.snapshot.queryParamMap.get('email') ?? '';
  private readonly token = this.route.snapshot.queryParamMap.get('token') ?? '';

  readonly loading = signal(false);
  readonly done = signal(false);
  readonly apiError = signal<ApiError | null>(null);
  readonly bannerMessage = () =>
    this.email && this.token ? formErrorMessage(this.apiError()) : 'El enlace no es válido. Pedí uno nuevo.';
  readonly serverFieldError = (field: string) => fieldError(this.apiError(), field);

  readonly form = this.fb.nonNullable.group(
    {
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

  submit(): void {
    if (this.form.invalid || this.loading() || !this.email || !this.token) {
      return;
    }

    this.loading.set(true);
    this.apiError.set(null);

    this.authResource
      .resetPassword({ email: this.email, token: this.token, ...this.form.getRawValue() })
      .subscribe({
        next: () => {
          this.loading.set(false);
          this.done.set(true);
        },
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
