import { Injectable, signal } from '@angular/core';

/**
 * Estado del panel lateral (.cc-sidenav) del workspace-shell: en mobile actúa
 * como drawer superpuesto (mobileOpen), en desktop se puede retraer del todo
 * (desktopCollapsed) para ganar espacio. Ambos signals son independientes a
 * propósito - cada uno solo importa en su propio breakpoint (ver _layout.scss).
 */
@Injectable({ providedIn: 'root' })
export class ShellNavService {
  readonly mobileOpen = signal(false);
  readonly desktopCollapsed = signal(false);

  openMobile(): void {
    this.mobileOpen.set(true);
  }

  closeMobile(): void {
    this.mobileOpen.set(false);
  }

  toggleDesktop(): void {
    this.desktopCollapsed.update((collapsed) => !collapsed);
  }

  /** Botón único (mobile y desktop) para minimizar el panel: cierra el drawer si está en mobile, lo retrae si está en desktop. */
  minimize(): void {
    this.mobileOpen.set(false);
    this.desktopCollapsed.set(true);
  }
}
