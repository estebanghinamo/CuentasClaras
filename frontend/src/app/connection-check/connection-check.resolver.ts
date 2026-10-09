import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { AuthResource } from '../core/resources/auth.resource';
import { checkConnection, ConnectionStatus } from './check-connection';

export type { ConnectionStatus } from './check-connection';

export const connectionCheckResolver: ResolveFn<ConnectionStatus> = () => {
  const authResource = inject(AuthResource);
  return checkConnection(authResource);
};
