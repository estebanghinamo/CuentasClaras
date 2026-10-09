import { ApplicationConfig, inject, provideAppInitializer, provideBrowserGlobalErrorListeners, provideZoneChangeDetection } from '@angular/core';
import { provideRouter, withComponentInputBinding, withRouterConfig } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { MAT_DATE_LOCALE, provideNativeDateAdapter } from '@angular/material/core';
import { MAT_DIALOG_DEFAULT_OPTIONS } from '@angular/material/dialog';
import { MAT_FORM_FIELD_DEFAULT_OPTIONS } from '@angular/material/form-field';

import { routes } from './app.routes';
import { authTokenInterceptor } from './core/interceptors/auth-token.interceptor';
import { apiErrorInterceptor } from './core/interceptors/api-error.interceptor';
import { globalErrorInterceptor } from './core/interceptors/global-error.interceptor';
import { loadingInterceptor } from './core/interceptors/loading.interceptor';
import { SessionService } from './core/services/session.service';

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideZoneChangeDetection({ eventCoalescing: true }),
    // paramsInheritanceStrategy 'always': por default ('emptyOnly') una ruta
    // hija con path propio (ej. 'categories' bajo ':workspaceId') NO hereda los
    // params del padre en su propio paramMap - con 'always' sí, así cualquier
    // página bajo /w/:workspaceId/* puede leer workspaceId directo sin subir a
    // .parent a mano en cada una (ver core/guards/, features/*/pages/*.page.ts).
    provideRouter(routes, withComponentInputBinding(), withRouterConfig({ paramsInheritanceStrategy: 'always' })),
    // CRITICO: globalErrorInterceptor va PRIMERO a propósito - los interceptores
    // envuelven la respuesta en orden inverso al array (loading -> apiError ->
    // authToken -> globalError), así que este es el ÚLTIMO en ver el error,
    // después de que apiErrorInterceptor lo normalizó a ApiError y de que
    // authTokenInterceptor ya agotó su refresh silencioso (si el token estaba
    // vencido). Si se pone en otro orden, el modal de error se dispara ANTES
    // de que un TOKEN_EXPIRED se resuelva solo - ver global-error.interceptor.ts.
    provideHttpClient(withInterceptors([globalErrorInterceptor, authTokenInterceptor, apiErrorInterceptor, loadingInterceptor])),
    provideAnimationsAsync(),
    { provide: MAT_DATE_LOCALE, useValue: 'es-AR' },
    provideNativeDateAdapter(),
    // subscriptSizing 'dynamic' en vez del default 'fixed' de Material: con
    // 'fixed' el área de mat-hint/mat-error reserva altura para UNA sola
    // línea, así que un hint largo (ej. pay-service-dialog) se desborda y
    // pisa lo que viene después en el diálogo (bug real reportado por el
    // usuario - letras superpuestas en varios modales). 'dynamic' hace que
    // esa área crezca con el contenido en vez de recortarlo.
    { provide: MAT_FORM_FIELD_DEFAULT_OPTIONS, useValue: { subscriptSizing: 'dynamic' } },
    // maxWidth 'min(480px, 92vw)': cada dialog.open() del proyecto pasa un
    // width fijo en px (ej. '480px'). Eso por sí solo NO es responsive - en
    // una pantalla de celular angosta el diálogo se desborda del viewport y
    // el contenido se ve comprimido/roto (bug real reportado por el usuario:
    // "Nuevo gasto" ilegible en mobile). maxWidth sí limita el ancho real
    // incluso cuando width pide más, así que este default global lo arregla
    // para TODOS los diálogos sin tocar cada dialog.open() uno por uno.
    { provide: MAT_DIALOG_DEFAULT_OPTIONS, useValue: { maxWidth: 'min(480px, 92vw)' } },
    provideAppInitializer(() => inject(SessionService).hydrate()),
  ]
};
