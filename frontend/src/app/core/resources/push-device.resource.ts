import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { RegisterPushDeviceRequest } from '../models/notification.models';

@Injectable({ providedIn: 'root' })
export class PushDeviceResource {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/push-devices`;

  register(request: RegisterPushDeviceRequest): Observable<void> {
    return this.http.post<void>(this.base, request);
  }

  unregister(token: string): Observable<void> {
    return this.http.delete<void>(this.base, { body: { token } });
  }
}
