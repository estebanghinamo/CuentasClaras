# Observability Lab (OpenSLO + Sloth + Prometheus + Grafana)

Laboratorio de estudio personal, **separado del stack de la app**. No se levanta
con el `docker-compose.yml` principal del proyecto y no forma parte del flujo
normal de desarrollo. Se levanta a mano solo cuando querés practicar.

## Qué es cada pieza

- **OpenSLO** (`sloth/login-slo.yaml`): el archivo YAML donde definís el SLO
  ("99% de logins exitosos en <500ms"). Es solo texto, no corre nada.
- **Sloth**: lee ese YAML y genera automáticamente reglas de Prometheus
  (`recording rules` + `alerting rules` de error budget / burn rate).
- **Prometheus**: scrapea el endpoint `/metrics` de Laravel cada 15s y guarda
  la serie temporal. También evalúa las reglas que generó Sloth.
- **Grafana**: dashboard visual conectado a Prometheus. Acá es donde "ves" el
  SLO, el error budget consumido, latencias, etc.

## Prerrequisito en el backend

Necesitás el paquete `spatie/laravel-prometheus` instalado y un endpoint
`/metrics` expuesto. Ver sección "Instalación en Laravel" más abajo.

## Cómo levantarlo

```bash
cd observability-lab
docker compose up -d
```

Esto NO toca el docker-compose.yml del proyecto ni tus contenedores de la app.
Solo asume que tu backend Laravel ya está corriendo en el host y accesible
(por defecto se apunta a `host.docker.internal:8000`, el puerto típico de
`php artisan serve`).

## Cómo apagarlo

```bash
cd observability-lab
docker compose down
```

Con `down -v` además borrás los datos históricos guardados en Prometheus.

## URLs cuando está levantado

- Grafana: http://localhost:3000 (user/pass: admin/admin, te va a pedir cambiarla)
- Prometheus: http://localhost:9090
- Sloth corre una vez y termina (no queda como servicio), genera el archivo
  `prometheus/sloth-rules.yaml` que Prometheus carga.

## Detalles verificados (para no repetir la depuración)

- El endpoint real es `/api/metrics` (Laravel antepone `api/` a todo lo
  definido en `routes/api.php`), no `/metrics` a secas. Ya está así en
  `prometheus/prometheus.yml`.
- Las métricas quedan con el prefijo `app_` (namespace pasado a
  `getOrRegisterCounter`/`Histogram`): `app_http_requests_total`,
  `app_http_request_duration_seconds_*`. Los queries de Sloth y el dashboard
  ya usan esos nombres.
- El YAML de `sloth/login-slo.yaml` está en el formato **nativo de Sloth**
  (`version: prometheus/v1`), no en OpenSLO puro: la versión de Sloth usada
  acá (`slok/sloth:latest`) no logró parsear el YAML de OpenSLO probado. Los
  conceptos (SLI, objective, ventana, error budget) son los mismos.

## Flujo de uso típico

1. Levantás el backend Laravel normalmente (`php artisan serve` o tu docker-compose).
2. Generás algo de tráfico al endpoint de login (real o con un script de prueba).
3. `docker compose up -d` acá en `observability-lab/`.
4. Entrás a Grafana, dashboard "Login SLO", y ves latencia, error rate, y
   burndown del error budget.
5. Cuando termines de estudiar: `docker compose down`.

No hace falta reconstruir nada entre usos salvo que cambies el YAML de Sloth
(ahí corré `docker compose up sloth` de nuevo para regenerar las reglas, y
`docker compose restart prometheus` para que las recargue).
