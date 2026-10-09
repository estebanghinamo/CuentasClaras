import { ChangeDetectionStrategy, Component, effect, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatDividerModule } from '@angular/material/divider';
import { MatMenuModule } from '@angular/material/menu';
import { MatTooltipModule } from '@angular/material/tooltip';
import { NotificationBell } from '../../../shared/components/notification-bell/notification-bell';
import { WORKSPACE_TYPE_ICONS, WORKSPACE_TYPE_LABELS } from '../../../shared/utils/workspace-type.util';
import { WorkspaceType } from '../../models/workspace.models';
import { OptionalSidebarSection } from '../../models/sidebar-section.models';
import { AuthService } from '../../services/auth.service';
import { ShellNavService } from '../../services/shell-nav.service';
import { SidebarSectionsService } from '../../services/sidebar-sections.service';
import { WorkspaceContextService } from '../../services/workspace-context.service';
import { SidebarSectionsDialog } from '../sidebar-sections-dialog/sidebar-sections-dialog';

interface NavItem {
  label: string;
  icon: string;
  link: string[];
}

// Estas 4 son las de uso más frecuente día a día - quedan fijas en la barra
// inferior de mobile (espacio físico limitado a 4 + botón "Más"). En el
// sidebar completo hay una quinta fija (Ingresos, ver FIXED_NAV_ITEMS abajo).
const PRIMARY_NAV_LABELS = ['Dashboard', 'Gastos', 'Servicios', 'Cuotas'];

// Fijas en el sidebar completo (no pasan por el sistema de "Agregar sección").
const FIXED_NAV_ITEMS: (Omit<NavItem, 'link'> & { path: string })[] = [
  { label: 'Dashboard', icon: 'dashboard', path: 'dashboard' },
  { label: 'Ingresos', icon: 'payments', path: 'income' },
  { label: 'Gastos', icon: 'shopping_cart', path: 'expenses' },
  { label: 'Servicios', icon: 'receipt_long', path: 'services' },
  { label: 'Cuotas', icon: 'credit_card', path: 'installments' },
  // M-13/M-18 (2026-09-22): sin item opcional posible - closings/suggestions
  // no están en el enum de SetSidebarSectionsRequest (backend), decisión
  // confirmada con el usuario de sumarlas como fijas en vez de tocar backend.
  { label: 'Cierres', icon: 'event_available', path: 'closings' },
  { label: 'Sugerencias', icon: 'lightbulb', path: 'suggestions' },
];

// M-25: un workspace 'shared_settlement' (Gastos compartidos) no tiene
// Ingresos/Servicios/Cuotas/Presupuestos/Monedero/Metas ni "Agregar sección"
// (no tienen sentido para un evento puntual) - Miembros/Actividad pasan de
// opcionales a siempre visibles, y "Liquidación" reemplaza al Dashboard.
const SETTLEMENT_NAV_ITEMS: (Omit<NavItem, 'link'> & { path: string })[] = [
  { label: 'Liquidación', icon: 'balance', path: 'settlement' },
  { label: 'Gastos', icon: 'shopping_cart', path: 'expenses' },
  { label: 'Miembros', icon: 'group', path: 'members' },
  { label: 'Actividad', icon: 'history', path: 'activity' },
];
const SETTLEMENT_PRIMARY_NAV_LABELS = ['Liquidación', 'Gastos', 'Miembros'];

// Secciones opcionales del sidebar (ver sidebar-sections-dialog.ts) mapeadas a
// su NavItem. El code coincide con sidebar_sections.code en la base.
const OPTIONAL_NAV_ITEMS: Record<OptionalSidebarSection, Omit<NavItem, 'link'> & { path: string }> = {
  budgets: { label: 'Presupuestos', icon: 'pie_chart', path: 'budgets' },
  savings: { label: 'Monedero', icon: 'savings', path: 'savings' },
  goals: { label: 'Metas', icon: 'flag', path: 'goals' },
  members: { label: 'Miembros', icon: 'group', path: 'members' },
  activity: { label: 'Actividad', icon: 'history', path: 'activity' },
};

/**
 * Shell de un workspace específico (/w/:workspaceId/...): Categorías,
 * Miembros, Actividad, Configuración. Se entra clickeando una fila en
 * /workspaces (general-shell) o eligiendo otro workspace desde el selector
 * (M-19, 2026-09-23): el nombre del workspace en el sidenav es un botón que
 * abre un mat-menu con el resto de los workspaces del usuario (navega directo
 * a /w/{id}, sin pasar por /workspaces) + "Ver todos mis workspaces".
 *
 * Navegación (2026-09-19): con 8 secciones no entran todas en la barra
 * inferior de mobile - se muestran las 4 más usadas (PRIMARY_NAV_LABELS) más
 * un botón "Más" que abre el panel lateral completo como drawer superpuesto
 * (ShellNavService.mobileOpen). El panel tiene un único botón "minimizar"
 * (mismo ícono/lugar en mobile y desktop, ShellNavService.minimize()): en
 * mobile cierra el drawer, en desktop lo retrae del todo para ganar espacio.
 * El botón "menu" del topbar solo aparece cuando el panel ya está retraído en
 * desktop, para poder volver a abrirlo. La flecha "volver" del topbar es SOLO
 * mobile (.cc-hide-desktop); en desktop, "Ver todos mis workspaces" dentro del
 * selector cumple ese rol.
 */
@Component({
  selector: 'app-workspace-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, MatButtonModule, MatMenuModule, MatDividerModule, MatTooltipModule, NotificationBell],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './workspace-shell.component.html',
})
export class WorkspaceShellComponent {
  protected readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly dialog = inject(MatDialog);
  protected readonly context = inject(WorkspaceContextService);
  protected readonly shellNav = inject(ShellNavService);
  protected readonly sidebarSections = inject(SidebarSectionsService);

  constructor() {
    // El shell se reutiliza al cambiar de workspace (mismo route, solo cambia
    // el param) - hay que re-cargar las secciones cada vez que cambia el id.
    effect(() => {
      const workspaceId = this.context.activeWorkspaceId();
      if (workspaceId) {
        this.sidebarSections.ensureLoaded(workspaceId);
      }
    });

    // M-19: la lista completa (para el selector del menú) solo se carga en
    // /workspaces (general-shell) - acá hace falta también para poder listar
    // "los demás" workspaces sin abandonar el shell actual.
    this.context.loadAll().subscribe();
  }

  otherWorkspaces() {
    const activeId = this.context.activeWorkspaceId();
    return this.context.workspaces().filter((w) => w.id !== activeId);
  }

  typeLabel(type: WorkspaceType): string {
    return WORKSPACE_TYPE_LABELS[type];
  }

  typeIcon(type: WorkspaceType): string {
    return WORKSPACE_TYPE_ICONS[type];
  }

  switchTo(workspaceId: number): void {
    this.router.navigateByUrl(`/w/${workspaceId}`);
  }

  isSettlementWorkspace(): boolean {
    return this.context.membership()?.type === 'shared_settlement';
  }

  navItems(): NavItem[] {
    const workspaceId = this.context.activeWorkspaceId();
    const isSettlement = this.isSettlementWorkspace();
    const baseItems = isSettlement ? SETTLEMENT_NAV_ITEMS : FIXED_NAV_ITEMS;

    const items: NavItem[] = baseItems.map((item) => ({
      label: item.label,
      icon: item.icon,
      link: ['/w', String(workspaceId), item.path],
    }));

    if (!isSettlement) {
      for (const code of this.sidebarSections.enabled()) {
        const optional = OPTIONAL_NAV_ITEMS[code];
        items.push({ label: optional.label, icon: optional.icon, link: ['/w', String(workspaceId), optional.path] });
      }
    }

    if (this.context.membership()?.role === 'owner') {
      items.push({ label: 'Configuración', icon: 'settings', link: ['/w', String(workspaceId), 'settings'] });
    }

    return items;
  }

  openAddSection(): void {
    const workspaceId = this.context.activeWorkspaceId();
    if (!workspaceId) {
      return;
    }

    this.dialog.open(SidebarSectionsDialog, {
      data: { workspaceId, enabled: this.sidebarSections.enabled() },
      width: '400px',
    });
  }

  primaryNavItems(): NavItem[] {
    const items = this.navItems();
    const labels = this.isSettlementWorkspace() ? SETTLEMENT_PRIMARY_NAV_LABELS : PRIMARY_NAV_LABELS;

    return labels.map((label) => items.find((item) => item.label === label)).filter((item): item is NavItem => item !== undefined);
  }

  logout(): void {
    this.authService.logout().subscribe(() => this.router.navigateByUrl('/auth/login'));
  }

  userInitials(): string {
    const name = this.authService.currentUser()?.name ?? '';
    return name
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }
}
