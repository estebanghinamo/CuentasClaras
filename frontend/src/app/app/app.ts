import { Component, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { LoadingService } from '../core/services/loading.service';
import { Spinner } from '../shared/components/spinner/spinner';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, Spinner],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App {
  protected readonly loading = inject(LoadingService);
}
