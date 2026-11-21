# Plataforma colaborativa de comparación de precios — Proyecto Nuclear VI

Diseño, evolución y evaluación de la calidad de una plataforma colaborativa de comparación de precios entre tiendas de barrio, minimercados y supermercados de **Armenia y el Quindío**.

Proyecto del Seminario Nuclear 4 · Ingeniería de Software · Corporación Universitaria Empresarial Alexander von Humboldt · Periodo II-2026.

| Asignatura | Docente |
|---|---|
| Arquitectura de Software II | Rubén Darío Díaz Bautista |
| Modelos de Calidad | Rubén Darío Díaz Bautista |

## El problema

Un mismo producto puede tener precios muy distintos según el establecimiento, pero el consumidor rara vez tiene información para comparar antes de comprar. La plataforma permite que los usuarios registren los precios que observan y que cualquier persona los compare. El reto técnico es presentar como **confiable, actualizada y neutral** información que proviene de fuentes con distinta confiabilidad e intereses.

**Alcance:** plataforma informativa. Fuera del alcance: venta en línea, pagos, pedidos y domicilios.

## Estado

| Corte | Entregables | Estado |
|---|---|---|
| 1 (30 %) | Arquitectura v1 + ADRs · Plan de calidad + especificación de requisitos | 🟢 Documentos completos · 🔴 falta la evidencia de campo de la Fase 1 (entrevistas, encuesta, benchmarking con citas APA) |
| 2 (30 %) | Núcleo desplegado · Análisis de impacto y arquitectura v2 · Informe TDD · Prueba de usabilidad | 🟢 Núcleo de extremo a extremo integrado en `main` (PR #9) · 🔴 falta el despliegue en Railway y la evidencia primaria de usabilidad |
| 3 (40 %) | Sistema integrado · Evaluación de arquitectura · Evaluación de calidad · Documento resumen | ⚪ Pendiente |

**Verificación automática (CI en GitHub Actions, cada PR y cada push a `main`):**

| Comprobación | Resultado en `main` (2026-11-16) |
|---|---|
| Pruebas Pest, unitarias e integración con PostgreSQL + PostGIS | 153 en verde |
| Cobertura de líneas del dominio, pcov (M-13; el CI exige ≥ 80 %) | 97,9 % |
| PHPStan nivel 8 (M-14) | 0 errores |
| Deptrac por contexto (AR-01, AR-02) y por capa | 0 violaciones |
| Vitest y build de la PWA | 10 pruebas en verde |
| Lint del contrato OpenAPI con Spectral | Sin errores |

## Cómo ejecutarlo (local)

Requisitos: Docker y Docker Compose.

```bash
cp .env.example .env            # variables de la base de datos
make install                    # construye imágenes, instala dependencias y genera APP_KEY
make up                         # api, worker, nginx, postgis y pwa
make migrate seed               # esquema + datos de DEMOSTRACIÓN (establecimientos ficticios)
```

- PWA: <http://localhost:5173> (Vite reenvía `/v1` a la API, sin CORS).
- API: <http://localhost:8080/v1> — contrato en [`docs/03-arquitectura/api/openapi.yaml`](docs/03-arquitectura/api/openapi.yaml).
- Pruebas: `make test` (Pest), `make test-arch` (Deptrac por contexto y por capa), `make lint` (PHPStan),
  `cd frontend && npm test` (Vitest).

> Los datos de la semilla son **ilustrativos**: 8 establecimientos ficticios de Armenia y precios inventados
> (fuente `EQUIPO_SEMILLA`). Sirven para la demostración; no son observaciones de campo.

Endpoints implementados: `GET /v1/productos`, `GET /v1/productos/{id}`, `GET /v1/establecimientos`,
`POST /v1/reportes-precio` (con `Idempotency-Key`), `GET /v1/comparaciones` y `GET /v1/comparaciones/resumen`.

## Despliegue en Railway (ADR-0013)

Las imágenes de producción están en [`docker/railway/`](docker/railway) y se probaron localmente
(la API migra al arrancar y responde; la web sirve la PWA con rutas de SPA). El despliegue en sí lo hace el
equipo con su cuenta:

1. Crear un proyecto en Railway y agregar **PostgreSQL**. En la consola SQL del servicio, ejecutar
   `CREATE EXTENSION IF NOT EXISTS postgis; CREATE EXTENSION IF NOT EXISTS unaccent;`
   (si la plantilla de PostgreSQL no trae PostGIS, usar la plantilla «PostGIS» de Railway).
2. Servicio **api** desde este repositorio. Usa el Dockerfile `docker/railway/api.Dockerfile` (declarado en `.railway/railway.ts`).
   Variables:
   - `APP_KEY` (generarla con `php artisan key:generate --show`)
   - `APP_URL`
   - `DB_CONNECTION=pgsql`
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (referencias a las del servicio PostgreSQL)
   - `CORS_ALLOWED_ORIGINS=https://<dominio-de-la-web>`

   Generar un dominio público.
3. Servicio **worker** desde el mismo repositorio y la misma imagen. Usa las mismas variables y este comando
   de inicio: `php artisan queue:work --queue=domain-events --tries=3`. Sin el worker, los reportes no llegan
   a la comparación.
4. Servicio **web**. Usa el Dockerfile `docker/railway/web.Dockerfile` y la variable de construcción
   `VITE_API_URL=https://<dominio-de-la-api>/v1`. Generar un dominio público.
5. (Opcional, solo para la demo) en el servicio api: `php artisan db:seed --force`.
6. URL públicas (desplegado el 2026-11-21, proyecto `precios-armenia`, entorno `production`):
   - PWA: https://web-production-5b284b25.up.railway.app
   - API: https://api-production-3b3251a9.up.railway.app/v1 (salud: `/up`)

La topología de servicios está declarada como código en [`.railway/railway.ts`](.railway/railway.ts)
(IaC de Railway, ADR-0020): `railway config plan` muestra las diferencias y `railway config apply` las aplica.
Las variables y los secretos no se gestionan desde el código: viven solo en Railway.

## Estructura del repositorio

```
.
├── backend/                              API en Laravel 11 (PHP 8.3)
│   ├── app/Domains/<Contexto>/           Un contexto delimitado por carpeta, cada uno con Domain/Application/Infrastructure:
│   │                                     Compartido, Catalogo, Establecimientos, Precios, Confianza, Comparacion,
│   │                                     Moderacion, Identidad, Ingesta (ADR-0002, ADR-0016)
│   ├── app/Http/                         Controladores v1, validación de peticiones y Problem Details (RFC 9457)
│   ├── database/                         Migraciones (PostGIS) y semilla de demostración
│   ├── tests/Unit · tests/Feature        Reglas de negocio (TDD) · API de extremo a extremo
│   └── deptrac.yaml · deptrac-capas.yaml · phpstan.neon
├── frontend/                             PWA en Vue 3 + Vite + Tailwind + Pinia; cola offline con Dexie (EC-09)
├── docker/                               Imágenes de desarrollo (php, node, nginx) y de producción (railway/)
├── docker-compose.yml · docker-compose.test.yml · Makefile · railway.json
├── docs/
│   ├── README.md                         Mapa de entregables por corte y lista de verificación
│   ├── 00-planteamiento/                 Planteamiento del problema y abordaje inicial de calidad (PDF)
│   ├── 01-requisitos/                    Especificación, matriz de trazabilidad, instrumentos de la Fase 1
│   ├── 02-calidad/                       Plan de calidad, informe de TDD y análisis estático, prueba de usabilidad, evidencias/
│   └── 03-arquitectura/
│       ├── documento-arquitectura-v1.md  Alternativas, modelo C4, riesgos y trade-offs
│       ├── documento-arquitectura-v2.md  Arquitectura actualizada con el módulo comercial
│       ├── analisis-impacto-modulo-comercial.md
│       ├── adr/                          Registro de decisiones (ADR-0001 a ADR-0016)
│       ├── api/openapi.yaml              Contrato de la API (OpenAPI 3.1, versión 0.2.0)
│       └── estudios/                     Estudio REST frente a GraphQL
├── .github/                              CI (workflows/ci.yml) y plantilla de pull request
└── CONTRIBUTING.md                       Flujo de trabajo del equipo
```

## Decisiones principales

| Decisión | Motivación | ADR |
|---|---|---|
| Monolito modular organizado por contextos de dominio | Modificabilidad (EC-05) con el costo operacional que puede asumir un equipo de 3 | [0002](docs/03-arquitectura/adr/0002-monolito-modular.md) |
| CQRS ligero: modelo de lectura proyectado mediante *outbox* | Consultas rápidas (EC-04) con actualidad acotada (EC-02) | [0003](docs/03-arquitectura/adr/0003-cqrs-ligero-outbox.md) |
| PWA con registro *offline-first* | Registro en ≤ 15 s con conectividad limitada (EC-01, EC-09) | [0006](docs/03-arquitectura/adr/0006-pwa-offline-first.md) |
| Modelo de confianza explicable: marcar, no borrar | Credibilidad de los datos sin perjudicar a comercios legítimos (EC-03) | [0007](docs/03-arquitectura/adr/0007-modelo-confianza.md) |
| Neutralidad garantizada por diseño | El orden de la comparación no depende de relaciones comerciales (EC-06) | [0009](docs/03-arquitectura/adr/0009-neutralidad-comparacion.md) |
| Contenedores en Railway, con AWS Well-Architected como marco | Despliegue reproducible al costo de un equipo académico (R-04) | [0013](docs/03-arquitectura/adr/0013-well-architected-framework-despliegue.md) |
| Fronteras por contexto verificadas con Deptrac en CI | Que el módulo comercial no altere el núcleo (EC-05, AR-01, AR-02) | [0016](docs/03-arquitectura/adr/0016-nucleo-compartido-y-fronteras-por-contexto.md) |

Modelo de calidad: **ISO/IEC 25010:2023** (producto), **ISO/IEC 25012** (datos) e **ISO/IEC 25040:2024** (evaluación). La justificación frente a McCall, Boehm y FURPS+ está en el [plan de calidad](docs/02-calidad/plan-aseguramiento-calidad.md).

## Cómo leer la documentación

Los diagramas (C4, secuencia, mapa de contextos) están escritos en [Mermaid](https://mermaid.js.org/) y GitHub los muestra directamente. El contrato de la API se puede visualizar pegando `docs/03-arquitectura/api/openapi.yaml` en [Swagger Editor](https://editor.swagger.io/).

## Equipo

| Integrante | GitHub |
|---|---|
| Carlos Augusto Aranzazu Jinete | [@carlosaugustocue](https://github.com/carlosaugustocue) |
| Miguel Ángel Rodríguez | [@miguel2607](https://github.com/miguel2607) |
| Sofía Giraldo González | [@Sofiagiraldo01](https://github.com/Sofiagiraldo01) |

> Cada integrante completa su usuario de GitHub en su propio *pull request* (ver [CONTRIBUTING.md](CONTRIBUTING.md)).

## Uso de inteligencia artificial generativa

Conforme a la guía del proyecto (§12.5), el equipo declara el uso de asistentes de IA generativa (Claude) como apoyo en la redacción y estructuración de la documentación y en la implementación del código. Los commits asistidos lo indican con un *trailer* `Co-Authored-By`. Todas las decisiones fueron revisadas por el equipo, que responde por ellas.
