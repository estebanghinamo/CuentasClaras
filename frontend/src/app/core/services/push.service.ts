import { Injectable, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { PushDeviceResource } from '../resources/push-device.resource';

/**
 * Push real (M-17): registra el token del dispositivo contra el backend
 * (POST /push-devices) y navega a la ruta indicada en el payload al tocar
 * una notificación. Solo hace algo en plataforma nativa (Capacitor) - en web
 * no hay tokens FCM que registrar, el canal push queda mudo a propósito
 * (in-app/email siguen andando igual).
 */
@Injectable({ providedIn: 'root' })
export class PushService {
  private readonly pushDevices = inject(PushDeviceResource);
  private readonly router = inject(Router);

  private currentToken: string | null = null;
  private initialized = false;

  async init(): Promise<void> {
    if (this.initialized || !Capacitor.isNativePlatform()) {
      return;
    }
    this.initialized = true;

    const permission = await PushNotifications.requestPermissions();
    if (permission.receive !== 'granted') {
      return;
    }

    await PushNotifications.addListener('registration', (token) => {
      this.currentToken = token.value;
      this.pushDevices
        .register({ token: token.value, platform: Capacitor.getPlatform() as 'android' | 'ios', device_name: null })
        .subscribe();
    });

    await PushNotifications.addListener('registrationError', (error) => {
      console.error('PushService: fallo el registro del token.', error);
    });

    await PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
      const route = action.notification.data?.['route'];
      if (typeof route === 'string' && route.length > 0) {
        this.router.navigateByUrl(route);
      }
    });

    await PushNotifications.register();
  }

  /** Al cerrar sesión: el token queda inútil para este usuario (login/logout compartido en el mismo dispositivo). */
  unregister(): void {
    if (!Capacitor.isNativePlatform() || this.currentToken === null) {
      return;
    }
    this.pushDevices.unregister(this.currentToken).subscribe();
    this.currentToken = null;
  }
}
