# Cómo ver las métricas — paso a paso

Esto asume tu flujo normal de desarrollo (ADR-004), sin cambiarlo:
- Terminal 1: `db` + `redis` vía Docker (docker-compose.yml de la raíz)
- Terminal 2: backend nativo (`php artisan serve`, puerto 8000)
- Terminal 3: frontend nativo (`ng serve`, puerto 4200)

El laboratorio de observabilidad es un **cuarto elemento aparte**, no
reemplaza nada de esto. Se agrega, se mira, y se apaga.

---

## Paso 1 — Levantá tu entorno de siempre (sin cambios)

Terminal 1:
```bash
docker compose up -d
```
Esto levanta `db` y `redis`, tal como ya hacés.

Terminal 2:
```bash
cd backend
php artisan serve
```
Backend en `http://localhost:8000`.

Terminal 3:
```bash
cd frontend
ng serve
```
Frontend en `http://localhost:4200`.

**No hace falta modificar nada de esto.** El middleware que mide las
métricas (`RecordRequestMetrics`) ya está activo automáticamente cada vez
que el backend corre — no es opcional, no hay que prenderlo.

---

## Paso 2 — Generá algo de uso real

Usá la app normalmente desde `http://localhost:4200`: iniciá sesión, andá
al dashboard, cargá un gasto, etc. Cada request que pasa por el backend
queda contado en las métricas, aunque el lab de observabilidad todavía
no esté levantado (las métricas viven en Redis, no se pierden).

Si querés generar tráfico rápido sin usar la UI, podés pegarle directo al
login desde una terminal:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"test@test.com\",\"password\":\"x\"}"
```

---

## Paso 3 — Levantá el laboratorio (cuarta terminal, o cuando quieras)

```bash
cd D:\CuentasClaras\observability-lab
docker compose up -d
```

(Si tu terminal ya está parada en `D:\CuentasClaras`, alcanza con
`cd observability-lab` a secas.)

Esto no toca tu `docker-compose.yml` de la raíz ni tus contenedores de
`db`/`redis` — es un `docker-compose.yml` **distinto**, con su propia red,
que vive solo en `observability-lab/`. Internamente se conecta a tu
backend en `localhost:8000` (vía `host.docker.internal`), que ya tenés
corriendo del Paso 1.

Esperá ~10 segundos a que los 3 contenedores (`sloth`, `prometheus`,
`grafana`) arranquen.

---

## Paso 4 — Mirá el dashboard

Abrí en el navegador: **http://localhost:3000**

- Usuario: `admin` / Contraseña: `admin` (te va a pedir cambiarla la
  primera vez, podés omitirlo con "Skip").
- En el menú lateral, buscá el dashboard **"Login SLO"** (ya viene creado,
  no hay que armarlo).

Ahí vas a ver 4 gráficos, todos con datos reales de tu uso:
1. **Request rate** — cuántos logins por segundo.
2. **Error rate 5xx** — errores de servidor (si no hiciste que el backend
   falle, esto va a estar en 0, es lo esperado).
3. **Latencia p95** — qué tan rápido responde el login.
4. **SLO objective status** — el cálculo de Sloth sobre si estás cumpliendo
   el objetivo definido (99% disponibilidad / 95% de requests <500ms).

Si generaste tráfico en el Paso 2 antes de levantar el lab, en cuanto
Prometheus haga su primer scrape (cada 15s) ya vas a ver esos datos
reflejados.

---

## Paso 5 — Cuando termines de mirar, apagalo

```bash
cd D:\CuentasClaras\observability-lab
docker compose down
```

Tu backend, frontend, `db` y `redis` siguen corriendo normal — esto solo
apaga Prometheus/Grafana/Sloth. No hace falta reiniciar nada más.

---

## Queries para otros endpoints (Prometheus, localhost:9090)

El middleware ya mide **todos** los endpoints, no solo el login. Estas
queries andan ya mismo en Prometheus (pestaña "Graph"/"Table"), sin tocar
nada del backend — solo cambian el valor de `route`.

**Login** (el que ya tiene SLO armado):
```
histogram_quantile(0.95, sum(rate(app_http_request_duration_seconds_bucket{route="api/auth/login"}[5m])) by (le))
```

**Dashboard** (candidato B del análisis original: endpoint "pesado", trae
datos de gastos/ingresos/presupuestos):
```
histogram_quantile(0.95, sum(rate(app_http_request_duration_seconds_bucket{route="api/workspaces/{workspaceId}/dashboard"}[5m])) by (le))
```

**Cierre mensual / allocate** (candidato C: operación de negocio crítica,
ahí importa más la tasa de éxito que la latencia):
```
histogram_quantile(0.95, sum(rate(app_http_request_duration_seconds_bucket{route="api/workspaces/{workspaceId}/closings/{year}/{month}/allocate"}[5m])) by (le))
```
Tasa de error (no-2xx) en vez de latencia, para este mismo endpoint:
```
sum(rate(app_http_requests_total{route="api/workspaces/{workspaceId}/closings/{year}/{month}/allocate", status!~"2.."}[5m]))
/
sum(rate(app_http_requests_total{route="api/workspaces/{workspaceId}/closings/{year}/{month}/allocate"}[5m]))
```

**Cualquier otro endpoint**: el patrón general es reemplazar el valor de
`route` por el que corresponda. Para saber el nombre exacto tal como lo ve
Prometheus, mirá el label real en las métricas ya recolectadas:
```
app_http_requests_total
```
(sin filtros) te lista todas las rutas que tuvieron tráfico, con su label
`route="..."` tal cual quedó registrado — copiás ese valor literal a la
query de arriba. Las rutas con parámetros (`{workspaceId}`, `{year}`,
etc.) quedan con las llaves literales, porque el middleware usa la
definición de la ruta de Laravel, no la URL resuelta con IDs reales — así
todas las llamadas al mismo endpoint (sin importar el ID) se agrupan
juntas en vez de crear una serie nueva por cada ID.

---

## ¿Dónde modificar si quiero agregar otro endpoint al dashboard?

Solo si querés experimentar más adelante, no es necesario para ver lo
que ya está armado:

- **Agregar un SLO nuevo** (ej. para `/dashboard` en vez de `/login`):
  editar `observability-lab/sloth/login-slo.yaml`, copiar un bloque y
  cambiar la ruta. Después: `docker compose up -d --force-recreate sloth prometheus`.
- **Agregar un gráfico al dashboard**: editar
  `observability-lab/grafana/provisioning/dashboards/login-slo.json`, y
  `docker compose restart grafana`.
- **No hace falta tocar nada del backend** para esto — el middleware ya
  mide *todas* las rutas automáticamente, con el nombre de la ruta como
  label (`route="..."`). Solo hay que apuntar el YAML/dashboard a la ruta
  que quieras ver.
