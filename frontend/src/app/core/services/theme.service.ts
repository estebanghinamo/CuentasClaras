import { Injectable, signal } from '@angular/core';
import { ThemePreference } from '../models/auth.models';

const STORAGE_KEY = 'cc-theme';

/**
 * Aplica la preferencia de tema (claro/oscuro/sistema) como clase en <html>,
 * que es lo que _tokens.scss ya espera (.dark-theme / .light-theme). 'system'
 * no agrega clase: sin clase explícita, el CSS cae solo a
 * prefers-color-scheme del SO (mismo comportamiento que había antes de esta
 * feature, sin JS de por medio).
 *
 * localStorage es solo una cache visual del último valor conocido, para
 * aplicar el tema apenas arranca la app (antes de que responda el backend)
 * y evitar un flash del tema equivocado - la fuente de verdad real es
 * siempre UserDto.theme, sincronizado por AuthService tras login/loadCurrentUser.
 */
@Injectable({ providedIn: 'root' })
export class ThemeService {
  readonly current = signal<ThemePreference>(this.readCached());

  apply(theme: ThemePreference): void {
    this.current.set(theme);
    this.cache(theme);

    const root = document.documentElement;
    root.classList.remove('dark-theme', 'light-theme');
    if (theme === 'dark') {
      root.classList.add('dark-theme');
    } else if (theme === 'light') {
      root.classList.add('light-theme');
    }
  }

  private readCached(): ThemePreference {
    try {
      const value = localStorage.getItem(STORAGE_KEY);
      return value === 'light' || value === 'dark' || value === 'system' ? value : 'system';
    } catch {
      return 'system';
    }
  }

  private cache(theme: ThemePreference): void {
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch {
      // Storage no disponible (privado/bloqueado) - solo se pierde la
      // optimización visual de evitar el flash, nada funcional.
    }
  }
}
