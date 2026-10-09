<div align="center">

<img src="https://readme-typing-svg.demolab.com?font=JetBrains+Mono&weight=600&size=24&duration=2600&pause=900&color=8A7FFF&center=true&vCenter=true&width=820&lines=Cuentas+Claras;Gesti%C3%B3n+de+gastos+e+ingresos+compartidos+por+workspace;Angular+20+%2B+Laravel+12+%2B+MySQL+%2B+Redis" alt="Typing SVG" />

<a href="https://github.com/estebanghinamo"><img src="https://img.shields.io/badge/⬅_Perfil-181717?style=for-the-badge&logo=github&logoColor=white" alt="Perfil"/></a>

</div>

---

### 💰 Sobre el proyecto

**Cuentas Claras** es una plataforma de gestión de gastos e ingresos compartidos por **workspace**: cada usuario puede crear espacios individuales o compartidos (conjunto, separado o de liquidación) para llevar sus finanzas personales o las de un grupo — pareja, familia, roomies —, con auditoría de cada cambio y cierres mensuales congelados.

- 🔐 **Autenticación** con Laravel Sanctum (Bearer) + **2FA TOTP** opcional, recuperación de contraseña y perfil de usuario.
- 🏠 **Workspaces** individuales y compartidos (conjunto / separado / liquidación), con **invitaciones** por código y roles (owner / member).
- 💸 **Ingresos, gastos y categorías**, con **importación de gastos por CSV**.
- 🔁 **Servicios recurrentes**, pagos mensuales y **cuotas**.
- 🐷 **Monedero de ahorro** y **metas de ahorro**.
- 📊 **Presupuestos con alertas**, dashboard con balance y proyección, y **health score financiero**.
- 🔒 **Cierre mensual congelado**: los períodos cerrados quedan protegidos de nuevas cargas.
- 🧾 **Exportación** de reportes a PDF / Excel / CSV.
- 🔔 **Notificaciones** in-app, push y email, más **recordatorios inteligentes** (smart suggestions).
- 🤝 **Gastos compartidos y liquidación** entre miembros del workspace.
- 🌎 **i18n** (es/en), accesibilidad y modo oscuro.
- 📱 App **mobile con Capacitor** (Android).
- 🗂️ Acceso a datos **100% vía Stored Procedures** (sin ORM ni migrations de Laravel) para integridad y trazabilidad.

---

### 🛠️ Stack

<div align="center">
<img src="https://skillicons.dev/icons?i=angular,ts,sass,laravel,php,mysql,redis,docker,androidstudio,git,github,vscode&perline=6" alt="tech stack"/>

<br>

![Sanctum](https://img.shields.io/badge/Laravel_Sanctum-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Capacitor](https://img.shields.io/badge/Capacitor-119EFF?style=for-the-badge&logo=capacitor&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)
![OpenAPI](https://img.shields.io/badge/OpenAPI-6BA539?style=for-the-badge&logo=openapiinitiative&logoColor=white)

</div>

Backend en **Laravel 12 (PHP 8.3)**, API pura en JSON, con **Sanctum** en modo Bearer token (sin JWT de terceros). Frontend en **Angular 20 standalone** con **Signals**, SCSS y `@angular/material`. Persistencia en **MySQL 8** exclusivamente por **Stored Procedures** (contrato JSON in / JSON out), sin Eloquent ni migrations para las tablas de negocio. **Redis** para cache y colas. App mobile con **Capacitor 8**. En desarrollo, backend y frontend corren nativos en el host; solo `db` y `redis` corren en **Docker**.

---

### 🧩 Arquitectura

Flujo de capas obligatorio en ambos lados:

```text
Angular  → Route → Resolver → Service → Resource → HttpClient
Laravel  → routes/api.php → Middleware → FormRequest → Controller → Service → Repository → CALL sp_xxx(JSON) → MySQL
```

- **Controller**: delgado, sin lógica de negocio.
- **Service**: reglas de negocio, permisos por workspace, auditoría.
- **Repository**: `json_encode` → `CALL sp_x(?)` → `json_decode`, sin lógica.
- **Stored Procedure**: persistencia e integridad, siempre `SELECT JSON_OBJECT(...) AS result`.

```text
CuentasClaras/
├── backend/    # Laravel 12 (API JSON) — Controllers · Services · Repositories · DTOs
├── frontend/   # Angular 20 standalone (Signals) — features · shared · core
├── db/         # schema.sql — tablas y Stored Procedures (fuente de verdad de datos)
└── docker-compose.yml   # db (MySQL) + redis para desarrollo
```

---

### 🛡️ Calidad y seguridad

CI en GitHub Actions corre en cada PR contra `main` (branch protection: PR + checks en verde para mergear):

- ✅ **PHPUnit** (backend) y **Karma/Jasmine** (frontend) — tests con cobertura.
- 🔍 **PHPStan / Larastan** (nivel 5) — análisis estático de tipos en PHP.
- 🔍 **ESLint / angular-eslint** — análisis estático en TypeScript/templates.
- 📦 **composer audit** / **npm audit** — vulnerabilidades conocidas en dependencias.
- 🕵️ **Gitleaks** — escaneo de secretos hardcodeados en todo el historial.
- 🕷️ **OWASP ZAP** (Baseline Scan) — DAST pasivo sobre el backend real.
- 📈 **SonarQube Cloud** — code smells, duplicación y cobertura combinada (foco en "new code" de cada PR).
- 📖 **OpenAPI / Swagger** (`l5-swagger`) — documentación interactiva de la API generada desde el propio código (`/api/documentation`).

---

### ▶️ Cómo correrlo

**Requisitos:** Docker Desktop (para `db` y `redis`) · PHP 8.3 + Composer · Node 22

```bash
git clone https://github.com/estebanghinamo/CuentasClaras.git
cd CuentasClaras

# primera vez
cd backend && composer install && cp .env.example .env && php artisan key:generate && cd ..
cd frontend && npm install && cd ..

# día a día — levanta MySQL + Redis en Docker
docker compose up -d
```

Backend y frontend corren nativos, cada uno en su propia terminal:

```bash
# Terminal 1
cd backend && php artisan serve   # http://127.0.0.1:8000

# Terminal 2
cd frontend && npm start          # ng serve, http://localhost:4200
```

El schema (`db/schema.sql`) se aplica solo la primera vez que se crea el volumen de `db`. No se usan Laravel migrations: todo el acceso a datos de negocio es vía Stored Procedures.

Tests: `php artisan test` (backend) y `npm test` (frontend). El workflow `.github/workflows/ci.yml` muestra el pipeline completo (tests, análisis estático, auditoría de dependencias, ZAP y SonarQube Cloud).

---

<div align="center"><sub>Córdoba, Argentina 🇦🇷</sub></div>
