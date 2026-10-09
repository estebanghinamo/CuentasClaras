export const environment = {
  production: true,
  // Único archivo de environment del proyecto (ver ADR-004): lo usan por igual
  // `ng serve` nativo, `ng build` y Capacitor/APK. Desde ADR-004, backend y frontend
  // corren nativos en el host (no en Docker) — el túnel de VS Code se reenvía
  // directo al puerto del backend nativo (8000) en vez de pasar por nginx (8080).
  // El día que haya un dominio real de producción, reemplazar SOLO esta línea.
  apiUrl: 'https://5dm3zm04-8000.brs.devtunnels.ms/api',
};
