import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { App } from './app/app/app';

// Aplica el último tema conocido (cacheado en localStorage por ThemeService)
// antes de que Angular monte nada, para evitar un flash del tema equivocado.
// Sin cache (primera visita) no hace nada: el CSS ya cae a prefers-color-scheme
// del SO por sí solo. La fuente de verdad real (UserDto.theme) la aplica
// ThemeService una vez que la app arranca y hay sesión.
try {
  const cached = localStorage.getItem('cc-theme');
  if (cached === 'dark' || cached === 'light') {
    document.documentElement.classList.add(`${cached}-theme`);
  }
} catch {
  // Storage no disponible - sin flash-prevention, pero nada funcional se rompe.
}

bootstrapApplication(App, appConfig)
  .catch((err) => console.error(err));
