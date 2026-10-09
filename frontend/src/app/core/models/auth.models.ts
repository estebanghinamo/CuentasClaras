export type ThemePreference = 'light' | 'dark' | 'system';

export interface UserDto {
  id: number;
  name: string;
  email: string;
  locale: string;
  theme: ThemePreference;
  two_factor_enabled: boolean;
  created_at: string;
}

export interface AuthTokensDto {
  access_token: string;
  access_expires_at: string;
  refresh_token: string;
  refresh_expires_at: string;
  user: UserDto;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface LoginRequest {
  email: string;
  password: string;
  device_name?: string;
}

export interface TwoFactorChallengeDetails {
  two_factor_required: true;
  challenge_token: string;
  expires_at: string;
}

export interface TwoFactorVerifyRequest {
  code: string;
}

export interface RefreshTokenRequest {
  refresh_token: string;
}

export interface TwoFactorSetupDto {
  secret: string;
  otpauth_url: string;
}

export interface TwoFactorConfirmRequest {
  code: string;
}

export interface TwoFactorConfirmedDto {
  recovery_codes: string[];
}

export interface TwoFactorDisableRequest {
  password: string;
}

export interface ForgotPasswordRequest {
  email: string;
}

export interface ResetPasswordRequest {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export interface ChangePasswordRequest {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateProfileRequest {
  name: string;
  locale: 'es' | 'en';
  theme: ThemePreference;
}
