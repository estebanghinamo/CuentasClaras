import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiSuccess } from '../models/api.models';
import {
  AuthTokensDto,
  ChangePasswordRequest,
  ForgotPasswordRequest,
  LoginRequest,
  RefreshTokenRequest,
  RegisterRequest,
  ResetPasswordRequest,
  TwoFactorConfirmRequest,
  TwoFactorConfirmedDto,
  TwoFactorDisableRequest,
  TwoFactorSetupDto,
  TwoFactorVerifyRequest,
  UpdateProfileRequest,
  UserDto,
} from '../models/auth.models';

@Injectable({ providedIn: 'root' })
export class AuthResource {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/auth`;

  register(request: RegisterRequest): Observable<AuthTokensDto> {
    return this.http.post<ApiSuccess<AuthTokensDto>>(`${this.base}/register`, request).pipe(map((r) => r.data));
  }

  login(request: LoginRequest): Observable<AuthTokensDto> {
    return this.http.post<ApiSuccess<AuthTokensDto>>(`${this.base}/login`, request).pipe(map((r) => r.data));
  }

  verifyTwoFactor(challengeToken: string, request: TwoFactorVerifyRequest): Observable<AuthTokensDto> {
    return this.http
      .post<ApiSuccess<AuthTokensDto>>(`${this.base}/2fa/verify`, request, {
        headers: { Authorization: `Bearer ${challengeToken}` },
      })
      .pipe(map((r) => r.data));
  }

  refresh(request: RefreshTokenRequest): Observable<AuthTokensDto> {
    return this.http.post<ApiSuccess<AuthTokensDto>>(`${this.base}/refresh`, request).pipe(map((r) => r.data));
  }

  logout(): Observable<void> {
    return this.http.post<void>(`${this.base}/logout`, {});
  }

  me(): Observable<UserDto> {
    return this.http.get<ApiSuccess<UserDto>>(`${this.base}/me`).pipe(map((r) => r.data));
  }

  enableTwoFactor(): Observable<TwoFactorSetupDto> {
    return this.http.post<ApiSuccess<TwoFactorSetupDto>>(`${this.base}/2fa/enable`, {}).pipe(map((r) => r.data));
  }

  confirmTwoFactor(request: TwoFactorConfirmRequest): Observable<TwoFactorConfirmedDto> {
    return this.http
      .post<ApiSuccess<TwoFactorConfirmedDto>>(`${this.base}/2fa/confirm`, request)
      .pipe(map((r) => r.data));
  }

  disableTwoFactor(request: TwoFactorDisableRequest): Observable<void> {
    return this.http.delete<void>(`${this.base}/2fa`, { body: request });
  }

  forgotPassword(request: ForgotPasswordRequest): Observable<void> {
    return this.http.post<void>(`${this.base}/forgot-password`, request);
  }

  resetPassword(request: ResetPasswordRequest): Observable<void> {
    return this.http.post<void>(`${this.base}/reset-password`, request);
  }

  changePassword(request: ChangePasswordRequest): Observable<void> {
    return this.http.put<void>(`${this.base}/password`, request);
  }

  updateProfile(request: UpdateProfileRequest): Observable<UserDto> {
    return this.http.put<ApiSuccess<UserDto>>(`${this.base}/profile`, request).pipe(map((r) => r.data));
  }
}
