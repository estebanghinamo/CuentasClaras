import { Injectable, signal } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { Preferences } from '@capacitor/preferences';

const ACCESS_KEY = 'cc.access_token';
const REFRESH_KEY = 'cc.refresh_token';

/**
 * Persistencia de tokens: localStorage en web, @capacitor/preferences en nativo
 * (ver ESPECIFICACION_TECNICA.md Apéndice C, P-07). El valor en memoria (signal)
 * es la fuente de verdad síncrona que usan los interceptors; el storage solo
 * sirve para sobrevivir un reinicio de la app.
 */
@Injectable({ providedIn: 'root' })
export class SessionService {
  private readonly isNative = Capacitor.isNativePlatform();
  private hydrated = false;

  readonly accessToken = signal<string | null>(null);
  readonly refreshToken = signal<string | null>(null);

  async hydrate(): Promise<void> {
    if (this.hydrated) {
      return;
    }
    this.hydrated = true;

    const [access, refresh] = await Promise.all([this.read(ACCESS_KEY), this.read(REFRESH_KEY)]);
    this.accessToken.set(access);
    this.refreshToken.set(refresh);
  }

  setTokens(accessToken: string, refreshToken: string): void {
    this.accessToken.set(accessToken);
    this.refreshToken.set(refreshToken);
    void this.write(ACCESS_KEY, accessToken);
    void this.write(REFRESH_KEY, refreshToken);
  }

  clear(): void {
    this.accessToken.set(null);
    this.refreshToken.set(null);
    void this.remove(ACCESS_KEY);
    void this.remove(REFRESH_KEY);
  }

  private async read(key: string): Promise<string | null> {
    if (this.isNative) {
      const { value } = await Preferences.get({ key });
      return value;
    }
    return localStorage.getItem(key);
  }

  private async write(key: string, value: string): Promise<void> {
    if (this.isNative) {
      await Preferences.set({ key, value });
      return;
    }
    localStorage.setItem(key, value);
  }

  private async remove(key: string): Promise<void> {
    if (this.isNative) {
      await Preferences.remove({ key });
      return;
    }
    localStorage.removeItem(key);
  }
}
