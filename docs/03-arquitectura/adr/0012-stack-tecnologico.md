# ADR-0012: Stack tecnológico

- **Estado:** Aceptado
- **Fecha:** 2026-11-10
- **Responsables:** Carlos Augusto Aranzazu Jinete · Miguel Ángel Rodríguez · Sofía Giraldo González
- **Escenarios relacionados:** EC-10 (TDD), EC-05 (pruebas de arquitectura), EC-01 (registro rápido), EC-09 (offline-first), R-04 (equipo y presupuesto)

## Contexto

La guía deja lenguajes y frameworks a decisión del equipo. Los criterios de selección, en orden de peso, son:

1. **Dominio previo del equipo** — el criterio más importante; reduce el riesgo de que la curva de aprendizaje consuma el tiempo de diseño y pruebas.
2. **Soporte de decimales exactos** — RN-02 prohíbe usar aritmética de punto flotante en ningún cálculo de precio o normalización por unidad.
3. **TDD expresivo** — EC-10 exige que las reglas RN-01 a RN-10 se desarrollen con ciclo rojo → verde → refactor; la suite de dominio debe correr en < 60 s.
4. **Pruebas de arquitectura** — AR-01 y AR-02 deben verificar automáticamente que los módulos no crucen sus fronteras (Comparación no depende de Comercial).
5. **Generación y validación de OpenAPI 3.1** — el contrato `openapi.yaml` es un mínimo exigible de la guía (§8.1) y debe mantenerse sincronizado con la implementación en CI.
6. **Ecosistema PWA offline-first** — el cliente necesita Service Worker, IndexedDB y cola de sincronización (ADR-0006).
7. **Costo operacional** — un solo proceso de runtime, docker compose, proveedor de nube gratuito/académico (R-04).

La arquitectura ya está decidida: monolito modular con contextos DDD (ADR-0002), CQRS ligero con tabla `outbox` procesada por un *worker* (ADR-0003), API REST (ADR-0004) y PostgreSQL + PostGIS (ADR-0005). El stack debe **soportar** esas decisiones, no imponerlas.

## Alternativas consideradas

### Backend

| Opción | TDD | Pruebas de arquitectura | OpenAPI 3.1 | Decimales exactos | Módulos / Outbox | Dominio equipo | Puntaje |
|---|---|---|---|---|---|---|---|
| **PHP 8.3 + Laravel 11** | Pest PHP (sobre PHPUnit) — muy expresivo para dominio | **Deptrac** — equivalente directo de ArchUnit | Dedoc/Scramble (auto-genera sin anotaciones) | `brick/money` + `bcmath` nativo | Bounded contexts en `app/Domains/`; Queue driver `database` = outbox natural | **Alto** | **23** |
| Java 21 + Spring Boot (Spring Modulith) | JUnit 5 / AssertJ | ArchUnit + Spring Modulith nativo | springdoc-openapi | `BigDecimal` nativo | Spring Modulith encaja perfectamente; eventos con ApplicationEventPublisher | Medio | 20 |
| TypeScript + NestJS | Jest / Vitest | dependency-cruiser | @nestjs/swagger | decimal.js | Un solo lenguaje con la PWA; módulos NestJS; outbox manual | Medio | 17 |
| C# + ASP.NET Core | xUnit | NetArchTest | Swashbuckle | `decimal` nativo | Minimal API + Outbox con MassTransit | Bajo | 12 |
| Python + FastAPI | pytest | import-linter | nativo | `Decimal` | Tipado más débil; outbox manual | Bajo | 10 |

Escala: dominio Alto=5, Medio=3, Bajo=1; demás criterios 0–2. El dominio previo del equipo es determinante.

### Frontend / PWA

| Opción | PWA / Service Worker | IndexedDB | Un lenguaje con backend | Curva |
|---|---|---|---|---|
| **Vue 3 + Vite + vite-plugin-pwa** | Workbox integrado | Dexie.js | No (PHP ↔ JS/TS) | Baja para el equipo |
| React + Vite + vite-plugin-pwa | Workbox integrado | Dexie.js / idb | No | Media |
| Angular + @angular/service-worker | Angular SW | idb | No | Alta |
| Inertia.js (Laravel + Vue acoplados) | Sin soporte real de offline | No aplica | Sí | Baja |

> **Inertia.js descartado explícitamente:** su modelo de respuesta basado en HTML parcial es incompatible con una API REST documentada con OpenAPI (mínimo exigible §8.1) y con el modo offline-first que requiere ADR-0006. El cliente debe poder operar sin servidor y sincronizarse luego, lo que exige un contrato JSON puro.

## Decisión

**Backend:** PHP 8.3 + Laravel 11 (API JSON pura, sin Blade, sin Inertia)
**Frontend:** Vue 3 + Vite + vite-plugin-pwa (Workbox) + Dexie.js (IndexedDB)
**Base de datos:** PostgreSQL 16 + PostGIS (ya decidido en ADR-0005)
**Worker:** `php artisan queue:work --queue=domain-events` (mismo contenedor base, proceso separado)

### Herramientas por responsabilidad

| Responsabilidad | Herramienta | Justificación |
|---|---|---|
| TDD del dominio (RN-01…RN-10) | **Pest PHP 3** | Sintaxis `it('…')` / `expect(…)->toBe(…)` más expresiva que PHPUnit para lógica de dominio; corre en < 10 s para suites unitarias |
| Pruebas de arquitectura (AR-01, AR-02) | **Deptrac** (qossmic/deptrac) | Define capas y dependencias permitidas en YAML; falla el CI si un módulo accede al interior de otro o si Comparación importa algo de Comercial |
| Generación OpenAPI 3.1 | **Dedoc/Scramble** | Auto-genera el esquema desde los tipos de retorno y Form Requests de Laravel sin anotaciones adicionales; exporta a `openapi.yaml` para validación en CI |
| Validación de contrato en CI | **Spectral** (Stoplight) | Lint del `openapi.yaml` generado contra el `openapi.yaml` de referencia; falla si divergen |
| Decimales exactos (RN-02) | **brick/money** + extensión `bcmath` | Tipo `Money` inmutable; aritmética con precisión arbitraria; evita `float` en toda la capa de dominio |
| PostGIS (ADR-0005) | `DB::select()` con queries raw | Las funciones `ST_DWithin`, `ST_Distance` se pasan directamente; sin ORM GIS para evitar abstracción innecesaria |
| Outbox transaccional (ADR-0003) | **Laravel Queues driver `database`** | `DB::transaction(fn() => [$modelo->save(), dispatch(new DomainEvent(...))->afterCommit()])` garantiza atomicidad; la tabla `jobs` actúa como outbox; el *worker* es `queue:work` |
| Análisis estático | **PHPStan** nivel 8 + **Laravel IDE Helper** | Detecta errores de tipo antes de ejecutar; nivel 8 equivale a strict types en toda la base de código |
| Contenedores | **Docker** (php-fpm + nginx + postgresql) + `docker compose` | Despliegue reproducible (ADR-0011); imagen única para API y Worker |
| CI | **GitHub Actions** | Pipeline: `pest`, `deptrac`, `phpstan`, `scramble:export` + diff contra `openapi.yaml`, Playwright E2E |
| PWA Service Worker | **Workbox 7** vía vite-plugin-pwa | Estrategia `NetworkFirst` para comparaciones; `BackgroundSync` para cola de reportes offline (EC-09) |
| Sincronización offline | **Dexie.js** (IndexedDB) | API reactiva; guarda reportes pendientes con `Idempotency-Key` (UUID v4 generado en el cliente) |

### Estructura de carpetas del backend (bounded contexts)

```
app/
├── Domains/
│   ├── Precios/         Application/ Domain/ Infrastructure/
│   ├── Confianza/       Application/ Domain/ Infrastructure/
│   ├── Comparacion/     Application/ Domain/ Infrastructure/
│   ├── Catalogo/        Application/ Domain/ Infrastructure/
│   ├── Establecimientos/Application/ Domain/ Infrastructure/
│   ├── Moderacion/      Application/ Domain/ Infrastructure/
│   ├── Identidad/       Application/ Domain/ Infrastructure/
│   └── Ingesta/         Application/ Domain/ Infrastructure/
├── Http/Controllers/    (orquesta; no contiene lógica de dominio)
└── Providers/
```

Deptrac verificará que `Domain/` no dependa de `Infrastructure/` (arquitectura hexagonal dentro de cada módulo) y que ningún dominio acceda a las tablas o clases internas de otro.

## Consecuencias

- **Positivas:**
  - El equipo trabaja en un lenguaje que domina, reduciendo el riesgo operacional (R-04).
  - Pest PHP + Deptrac + PHPStan cubren los tres pilares de verificación automática que exige la guía: TDD (EC-10), fronteras de módulo (AR-01/AR-02) y análisis estático (M-14).
  - `brick/money` garantiza cumplimiento de RN-02 desde el tipo, no desde la disciplina del desarrollador.
  - Laravel Queues como outbox reutiliza infraestructura ya incluida en Laravel sin dependencias adicionales (R-04).
  - Vue 3 + vite-plugin-pwa cubre EC-01 y EC-09 con un ecosistema maduro y bien documentado.

- **Negativas / riesgos:**
  - Laravel no tiene el equivalente nativo de Spring Modulith para events internos; Deptrac debe configurarse con cuidado y ejecutarse en cada PR.
  - PHP-FPM arranca un proceso por petición; para 500 usuarios concurrentes en modo *stateless* es suficiente, pero si se supera esa carga se considerará **Laravel Octane con FrankenPHP** (sin cambio de código).
  - El frontend y el backend son repositorios/procesos separados; el CORS debe configurarse explícitamente en Laravel y validarse en CI.

- **Condiciones de revisión:**
  - Si la suite de dominio supera los 60 s (M-13), se migrará de `phpunit` subyacente a un runner paralelo (`--parallel` de Pest).
  - Si la carga supera los 500 VU en producción, se evaluará Laravel Octane sin necesidad de cambiar el stack.
  - La decisión de proveedor cloud se registra en ADR-0011 (pendiente de complementar).
