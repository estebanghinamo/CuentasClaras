import { Observable, catchError, map, of } from 'rxjs';
import { ApiError } from '../core/models/api.models';
import { AuthResource } from '../core/resources/auth.resource';

export interface ConnectionStatus {
  reachable: boolean;
  detail: string;
}

export function checkConnection(authResource: AuthResource): Observable<ConnectionStatus> {
  return authResource.me().pipe(
    map((): ConnectionStatus => ({ reachable: true, detail: 'Backend conectado (sesión activa)' })),
    catchError((error: ApiError) => {
      if (error.error?.code === 'UNAUTHENTICATED') {
        return of<ConnectionStatus>({ reachable: true, detail: 'Backend conectado (sin sesión iniciada)' });
      }
      return of<ConnectionStatus>({
        reachable: false,
        detail: `No se pudo contactar al backend (${error.error?.code ?? 'sin respuesta'})`,
      });
    }),
  );
}
