import { Injectable, inject } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';

@Injectable({ providedIn: 'root' })
export class ToastService {
  private readonly snackBar = inject(MatSnackBar);

  success(message: string): void {
    this.show(message, 'cc-toast-success');
  }

  error(message: string): void {
    this.show(message, 'cc-toast-error');
  }

  info(message: string): void {
    this.show(message, 'cc-toast-info');
  }

  private show(message: string, panelClass: string): void {
    this.snackBar.open(message, undefined, { duration: 4000, panelClass });
  }
}
