import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';
import { forkJoin } from 'rxjs';
import { ServiceDto, ServicePaymentListDto } from '../../core/models/service.models';
import { ServicePaymentsResource } from './service-payments.resource';
import { ServicesResource } from './services.resource';

export interface ServicesPageData {
  services: ServiceDto[];
  payments: ServicePaymentListDto;
}

/** Carga los servicios activos y los pagos del mes actual (pestaña "Este mes" por defecto). */
export const servicesResolver: ResolveFn<ServicesPageData> = (route) => {
  const workspaceId = Number(route.paramMap.get('workspaceId'));
  const now = new Date();

  return forkJoin({
    services: inject(ServicesResource).list(workspaceId, 'all'),
    payments: inject(ServicePaymentsResource).listForPeriod(workspaceId, now.getFullYear(), now.getMonth() + 1),
  });
};
