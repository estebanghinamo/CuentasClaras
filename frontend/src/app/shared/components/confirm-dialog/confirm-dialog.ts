import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';

export interface ConfirmDialogData {
  title: string;
  message: string;
  icon?: string;
  /** Si se omite, el diálogo queda en modo "alerta": un solo botón, sin opción de cancelar. */
  confirmLabel?: string;
  cancelLabel?: string;
  tone?: 'default' | 'danger';
}

/**
 * Reemplazo de confirm()/alert() nativos del navegador por un mat-dialog
 * propio, para confirmaciones (2 botones) y errores/avisos (1 botón, modo
 * "alerta" cuando no se pasa cancelLabel). Devuelve true/false al cerrar
 * (siempre true en modo alerta).
 */
@Component({
  selector: 'app-confirm-dialog',
  imports: [MatDialogModule, MatButtonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './confirm-dialog.html',
  styleUrl: './confirm-dialog.scss',
})
export class ConfirmDialog {
  protected readonly data = inject<ConfirmDialogData>(MAT_DIALOG_DATA);
  protected readonly dialogRef = inject(MatDialogRef<ConfirmDialog, boolean>);
}
