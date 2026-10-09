import { Component, computed, inject, input, signal } from '@angular/core';
import { AuthResource } from '../../core/resources/auth.resource';
import { checkConnection, ConnectionStatus } from '../check-connection';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-connection-check',
  templateUrl: './connection-check.page.html',
  styleUrl: './connection-check.page.scss',
})
export class ConnectionCheckPage {
  private readonly authResource = inject(AuthResource);

  connection = input.required<ConnectionStatus>();
  private readonly manualStatus = signal<ConnectionStatus | null>(null);
  status = computed(() => this.manualStatus() ?? this.connection());
  checking = signal(false);
  readonly apiUrl = environment.apiUrl;

  retry(): void {
    this.checking.set(true);
    checkConnection(this.authResource).subscribe((result) => {
      this.manualStatus.set(result);
      this.checking.set(false);
    });
  }
}
