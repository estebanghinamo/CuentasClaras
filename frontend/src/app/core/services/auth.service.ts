import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, catchError, of, tap } from 'rxjs';
import { AuthResource } from '../resources/auth.resource';
import {
  AuthTokensDto,
  LoginRequest,
  RegisterRequest,
  UpdateProfileRequest,
  UserDto,
} from '../models/auth.models';
import { PushService } from './push.service';
import { SessionService } from './session.service';
import { ThemeService } from './theme.service';
import { WorkspaceContextService } from './workspace-context.service';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly authResource = inject(AuthResource);
  private readonly session = inject(SessionService);
  private readonly workspaceContext = inject(WorkspaceContextService);
  private readonly pushService = inject(PushService);
  private readonly theme = inject(ThemeService);

  readonly currentUser = signal<UserDto | null>(null);
  readonly isAuthenticated = computed(() => this.currentUser() !== null);

  register(request: RegisterRequest): Observable<AuthTokensDto> {
    return this.authResource.register(request).pipe(tap((tokens) => this.applyTokens(tokens)));
  }

  login(request: LoginRequest): Observable<AuthTokensDto> {
    return this.authResource.login(request).pipe(tap((tokens) => this.applyTokens(tokens)));
  }

  verifyTwoFactor(challengeToken: string, code: string): Observable<AuthTokensDto> {
    return this.authResource
      .verifyTwoFactor(challengeToken, { code })
      .pipe(tap((tokens) => this.applyTokens(tokens)));
  }

  logout(): Observable<void> {
    return this.authResource.logout().pipe(
      tap(() => this.logoutLocal()),
      catchError(() => {
        this.logoutLocal();
        return of(undefined);
      }),
    );
  }

  logoutLocal(): void {
    this.pushService.unregister();
    this.session.clear();
    this.currentUser.set(null);
    this.workspaceContext.clear();
    this.theme.apply('system');
  }

  loadCurrentUser(): Observable<UserDto> {
    return this.authResource.me().pipe(tap((user) => this.setCurrentUser(user)));
  }

  updateProfile(request: UpdateProfileRequest): Observable<UserDto> {
    return this.authResource.updateProfile(request).pipe(tap((user) => this.setCurrentUser(user)));
  }

  private applyTokens(tokens: AuthTokensDto): void {
    this.session.setTokens(tokens.access_token, tokens.refresh_token);
    this.setCurrentUser(tokens.user);
  }

  private setCurrentUser(user: UserDto): void {
    this.currentUser.set(user);
    this.theme.apply(user.theme);
  }
}
