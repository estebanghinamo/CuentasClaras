import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { AuthLayout } from '../../../../shared/components/auth-layout/auth-layout';
import { Spinner } from '../../../../shared/components/spinner/spinner';
import { ApiError } from '../../../../core/models/api.models';
import { AuthService } from '../../../../core/services/auth.service';

@Component({
  selector: 'app-two-factor-page',
  imports: [ReactiveFormsModule, MatButtonModule, MatFormFieldModule, MatInputModule, AuthLayout, Spinner],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './two-factor.page.html',
})
export class TwoFactorPage {
  private readonly fb = inject(FormBuilder);
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  private readonly challengeToken = this.route.snapshot.queryParamMap.get('challenge') ?? '';
  private readonly returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    code: ['', [Validators.required, Validators.pattern(/^(\d{6}|[A-Za-z0-9]{10})$/)]],
  });

  constructor() {
    if (!this.challengeToken) {
      void this.router.navigateByUrl('/auth/login');
    }
  }

  submit(): void {
    if (this.form.invalid || this.loading() || !this.challengeToken) {
      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    const code = this.form.getRawValue().code.toUpperCase();

    this.authService.verifyTwoFactor(this.challengeToken, code).subscribe({
      next: () => this.router.navigateByUrl(this.returnUrl ?? '/'),
      error: (error: ApiError) => {
        this.loading.set(false);
        this.errorMessage.set(error.error?.message ?? 'Código incorrecto.');
      },
    });
  }
}
