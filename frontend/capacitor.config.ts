import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.cuentasclaras.app',
  appName: 'Cuentas Claras',
  webDir: 'dist/frontend/browser',
  plugins: {
    // M-17: notificacion visible con sonido aunque la app este en foreground.
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert'],
    },
  },
};

export default config;
