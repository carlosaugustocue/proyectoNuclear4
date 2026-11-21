# Matriz de trazabilidad — Núcleo v1

> Actualizada el 2026-11-16 con las pruebas implementadas en el corte 2 (§3 y §4).

> Parte del **Entregable 2 de Modelos de Calidad**. Relaciona requisito → escenario → característica/subcaracterística → medida → umbral → prueba → decisión arquitectónica.
> Las pruebas se nombran con su tipo: **U** = unitaria (TDD), **I** = integración, **C** = contrato API, **S** = sistema/E2E, **K** = carga, **US** = prueba con usuarios, **A** = accesibilidad, **AR** = prueba de arquitectura, **D** = auditoría de datos.

## 1. Requisitos no funcionales y escenarios

| RNF | Escenario | Característica / subcaracterística | Medida | Umbral (no aceptable / aceptable / objetivo) | Prueba | ADR |
|---|---|---|---|---|---|---|
| RNF-01 | EC-01 | Capacidad de interacción · operabilidad | % registros ≤ 15 s | < 80 % / ≥ 80 % / ≥ 90 % | US-01 (≥ 8 usuarios, red 3G simulada) | ADR-0006 |
| RNF-02 | EC-02 | Actualidad (25012) | Tiempo cambio → retiro como vigente | > 5 min / ≤ 5 min / ≤ 1 min | I-02 (reloj controlado + proyección) | ADR-0003 |
| RNF-03 | EC-03 | Credibilidad (25012); Seguridad · integridad, resistencia | Exhaustividad; tasa de falsos positivos | < 80 % / ≥ 80 % / ≥ 90 % · > 5 % / ≤ 5 % / ≤ 3 % | I-03 (conjunto etiquetado ≥ 200 reportes) | ADR-0007 |
| RNF-04 | EC-04 | Eficiencia de desempeño · comportamiento temporal, capacidad | p95 consulta; tasa de error | > 2 s / ≤ 2 s / ≤ 1 s · ≥ 1 % / < 1 % / < 0,1 % | K-01 (k6, 500 VU, 15 min) | ADR-0002, ADR-0003 |
| RNF-05 | EC-05 | Mantenibilidad · modularidad, modificabilidad | Cambios estructurales en núcleo; esfuerzo real/estimado | ≥ 1 / 0 / 0 · > 1,3 / ≤ 1,3 / ≤ 1,15 | AR-01 (dependencias entre módulos) + historial Git | ADR-0002 |
| RNF-06 | EC-06 | Adecuación funcional · corrección funcional | % casos donde promoción no altera orden y se distingue | < 100 % / 100 % / 100 % | U-10, C-06, AR-02 | ADR-0009 |
| RNF-07 | EC-07 | Seguridad · confidencialidad; Confidencialidad (25012) | Campos personales en respuestas públicas | ≥ 1 / 0 / 0 | C-07 (esquemas OpenAPI + respuestas reales) | ADR-0004 |
| RNF-08 | EC-08 | Capacidad de interacción · inclusividad | Violaciones A/AA automáticas; checklist manual | ≥ 1 / 0 / 0 · < 100 % / 100 % / 100 % | A-01 (axe), A-02 (manual, lector de pantalla) | ADR-0006 |
| RNF-09 | EC-09 | Fiabilidad · tolerancia a fallos | % registros offline sincronizados; duplicados | < 100 % / 100 % / 100 % · ≥ 1 / 0 / 0 | S-09 (modo avión simulado), I-09 (idempotencia) | ADR-0006 |
| RNF-10 | EC-10 | Mantenibilidad · capacidad de ser probado | Cobertura de líneas del dominio; duración suite | < 80 % / ≥ 80 % / ≥ 90 % · > 60 s / ≤ 60 s / ≤ 20 s | Reporte de cobertura en CI | ADR-0011 |
| RNF-11 | EC-03 | Trazabilidad (25012); Seguridad · responsabilidad | % observaciones con fuente y fecha; % decisiones de moderación registradas | < 100 % / 100 % / 100 % | D-11 (consulta a BD), I-20 | ADR-0007 |
| RNF-12 | EC-11 | Eficiencia de desempeño · comportamiento temporal; Adecuación funcional · corrección | p95 canasta; faltantes señalados sin sumar | > 3 s / ≤ 3 s / ≤ 1,5 s · < 100 % / 100 % / 100 % | K-02 (k6, 200 VU), U-11, U-12, U-13, C-14, C-15 | ADR-0017, ADR-0018, ADR-0019 |
| RNF-13 | EC-12 | Mantenibilidad · capacidad de ser probado; Flexibilidad · instalabilidad | % cambios defectuosos bloqueados; tiempo hasta staging; CVE críticas; tiempo de reversión | < 100 % / 100 % / 100 % · > 15 / ≤ 15 / ≤ 10 min · ≥ 1 / 0 / 0 · > 5 / ≤ 5 / ≤ 2 min | S-12, S-13, S-14 | ADR-0020, ADR-0021 |

## 2. Requisitos funcionales y reglas de negocio

| RF | Descripción corta | Regla(s) | Escenario | Prueba(s) | Módulo |
|---|---|---|---|---|---|
| RF-01 | Buscar producto | — | EC-04 | I-01, C-01 | Catálogo |
| RF-02 | Detalle con presentaciones | — | — | C-02 | Catálogo |
| RF-03 | Proponer producto | — | EC-01 | I-03b, S-03 | Catálogo |
| RF-04 | Fusionar duplicados | — | — (consistencia 25012) | I-04 | Catálogo / Moderación |
| RF-05 | Directorio | — | — | C-05 | Establecimientos |
| RF-06 | Cercanos por radio | — | EC-04 | I-06 (PostGIS) | Establecimientos |
| RF-07 | Proponer establecimiento | — | — | I-07 | Establecimientos |
| RF-08 | Registrar precio | RN-08 | EC-01 | U-08, C-08, S-08 | Precios |
| RF-09 | Registro offline | — | EC-09 | S-09, I-09 | Cliente PWA / Precios |
| RF-10 | Confirmar "sigue igual" | RN-05 | EC-01, EC-03 | U-05, S-10 | Precios / Confianza |
| RF-11 | Sugerir establecimiento cercano | — | EC-01, EC-07 | S-11 | Cliente PWA |
| RF-12 | Mis aportes | — | — | C-12 | Precios |
| RF-13 | Comparar | RN-09, RN-10 | EC-04, EC-06 | U-09, U-10, K-01, C-13 | Comparación |
| RF-14 | Comparar presentaciones distintas | RN-01, RN-02, RN-03 | EC-06 | U-01, U-02, U-03 | Comparación (dominio de Catálogo) |
| RF-15 | Filtros y criterio de orden visible | RN-10 | EC-06 | U-10, S-15 | Comparación |
| RF-16 | No mostrar como vigente lo vencido/no confiable | RN-04, RN-09 | EC-02, EC-03 | U-04, U-09, I-02 | Comparación / Confianza |
| RF-17 | Consulta sin cuenta | — | EC-04 | C-17 | API |
| RF-18 | Estado de confianza automático | RN-04 a RN-08 | EC-03 | U-04…U-08, I-03 | Confianza |
| RF-19 | Marcar dudoso | — | EC-03 | C-19 | Confianza |
| RF-20 | Cola de moderación | — | EC-03 | I-20, S-20 | Moderación |
| RF-21 | Límite de reportes | RN-08 | EC-03 | U-08, I-21 | Precios |
| RF-22 | Autorización de datos | — | EC-07 | S-22 | Identidad |
| RF-23 | Derechos del titular | — | EC-07 | I-23 | Identidad |
| RF-24 | Roles | — | EC-07 | C-24 (403 por rol) | Identidad |
| RF-25 | Importar SIPSA | — | — | I-25 (archivo de muestra) | Ingesta |
| RF-26 | Carga semilla | — | — | I-26 | Ingesta |

## 3. Reglas de negocio → pruebas TDD (corte 2)

| Regla | Suite | Casos mínimos |
|---|---|---|
| RN-01 | U-01 `NormalizadorUnidad` | g, kg, mL, L, unidad; multiempaque; cantidad 0 o negativa → error |
| RN-02 | U-02 `Dinero` | decimales exactos, redondeo solo al mostrar, sin flotantes |
| RN-03 | U-03 `Comparabilidad` | misma unidad base ok; distinta → error de dominio |
| RN-04 | U-04 `PoliticaVigencia` | dentro, en advertencia (1×–2×), vencida (> 2×), límites exactos, perecedero vs abarrote |
| RN-05 | U-05 `Corroboracion` | 2 reportantes ≤ 5 %, mismo reportante no cuenta, fuera de 48 h no cuenta |
| RN-06 | U-06 `DetectorAnomalias` | n ≥ 5 con MAD, n < 5 con historial, MAD = 0, umbral exacto 3,5 |
| RN-07 | U-07 `PoliticaReputacion` | cuenta nueva, cuenta con 5 aportes, cuenta suspendida |
| RN-08 | U-08 `LimiteAportes` | 30/h, 31.º rechazado, último del día reemplaza |
| RN-09 | U-09 `SelectorPrecioVigente` | combinaciones de estado y vigencia |
| RN-10 | U-10 `OrdenComparacion` | por precio/unidad, por distancia, empates, entrada con atributos comerciales ignorados |

Suites unitarias añadidas en el corte 2 al implementar el flujo de extremo a extremo:

| Regla | Suite | Casos mínimos |
|---|---|---|
| RN-01, RN-02 | `CalculadoraPrecioUnitario` | precio por kg, L o unidad; multiempaque; decimales exactos (escala 2, HALF_UP); unidad desconocida, empaque < 1 o cantidad ≤ 0 → error |
| RN-05, RN-06 | `EvaluadorConfianza` | la anomalía (POR_VERIFICAR) prevalece; corroborado → CONFIABLE; reporte único → NO_CORROBORADO |
| RN-07 | `PoliticaReemplazoVigente` | sin precio vigente → reemplaza; observación más antigua → no; cuenta nueva frente a precio corroborado → solo si difiere ≤ 5 %; precio vigente vencido → reemplaza |
| RN-04, RN-09 | `RedactorAdvertencia` | textos de «puede estar desactualizado», «histórico», «en verificación», «no confiable»; combinación de mensajes; singular «1 día» |
| RN-08 | `RegistroPrecio` (entidad) | crea la observación con fuente y clave de idempotencia; rechaza fechas futuras; emite `PrecioRegistrado` sin ubicación |

## 4. Pruebas implementadas (corte 2) → requisitos, escenarios y medidas

Estado al 2026-11-16, rama `feat/nucleo-e2e-comparacion`. Todas en verde: **153 ejecuciones Pest** (incluye los
conjuntos de datos que multiplican casos) y **10 pruebas Vitest**. Las pruebas Feature corren contra
PostgreSQL 16 + PostGIS con la cola en modo síncrono, así que recorren el flujo completo de eventos.
Cobertura medida de las líneas del dominio: **97,9 %** (M-13).

### 4.1 Backend — pruebas de API (integración + contrato)

| Archivo (`backend/tests/Feature/Api/V1/`) | Casos | Identificadores de la matriz | RF / RN | Escenario / medida |
|---|---|---|---|---|
| `CatalogoTest.php` | 7 | C-01, C-02, I-01 | RF-01, RF-02, RF-17 | EC-04 |
| `EstablecimientosTest.php` | 4 | C-05, I-06 (PostGIS) | RF-05, RF-06 | EC-04 |
| `ReportarPrecioTest.php` | 12 | C-08, I-09 (idempotencia), I-21, C-07 parcial | RF-08, RF-09, RF-21 · RN-08 | EC-07, EC-09 · M-10, M-12 |
| `OutboxPrecioRegistradoTest.php` | 3 | I-02 parcial (outbox) | RF-18 | EC-02, EC-09 |
| `ComparacionTest.php` | 21 | C-13, C-06, C-07, I-02, I-03, I-20 parcial | RF-13 a RF-18, RF-20 · RN-01, RN-04 a RN-07, RN-09, RN-10 | EC-02, EC-03, EC-06, EC-07 · M-09, M-10, M-15 |
| `CorsTest.php` | 2 | — (despliegue) | RF-17 | — (ADR-0013) |

Detalle de lo que verifica `ComparacionTest.php`:

| Caso | Requisito / regla |
|---|---|
| Normaliza por unidad base y ordena por precio por kilogramo | RN-01, RN-10, RF-14 |
| Declara el criterio de orden y que nadie paga por su posición | RF-15, EC-06, ADR-0009 |
| Empate de precio por unidad → gana el más reciente; orden por distancia con ubicación | RN-10 |
| Filtro por presentación; `Cache-Control` de 30 s | RF-13, EC-04 |
| Reporte aislado → NO_CORROBORADO, vigente, sin advertencia | RN-05, RN-09 |
| Dos reportantes ≤ 5 % en 48 h → CONFIABLE; el mismo reportante no se corrobora | RN-05 |
| Precio atípico → POR_VERIFICAR con advertencia y caso de moderación | RN-06, RF-20, EC-03 |
| Cuenta nueva no reemplaza un precio corroborado | RN-07 |
| El precio nuevo reemplaza al anterior | EC-02 (sin medir tiempo: M-02 queda para la prueba I-02 con reloj controlado) |
| Abarrote de 9 días con advertencia; > 2× la vigencia fuera por defecto; `incluirNoVigentes`; perecederos a 3 días | RN-04, RF-16, M-15 |
| Sin datos personales; solo campos del contrato, sin atributos comerciales | C-07, C-06, AR-02, EC-07 |
| 404 en Problem Details; 400 sin `productoId` u `orden=distancia` sin ubicación | RF-13 |
| Resumen del menor precio vigente por producto | RF-13 (tarjetas) |

### 4.2 Backend — pruebas unitarias de dominio

| Suite | Contexto | Casos | Regla |
|---|---|---|---|
| U-01 `NormalizadorUnidadTest` | Compartido | 8 | RN-01 |
| U-02 `DineroTest` · `PrecioTest` | Compartido | 4 · 6 | RN-02 |
| U-03 `ComparabilidadTest` | Compartido | 6 | RN-03 |
| `CalculadoraPrecioUnitarioTest` | Compartido | 6 | RN-01, RN-02 |
| `CoordenadasTest` | Compartido | 5 | RF-06 |
| U-04 `PoliticaVigenciaTest` | Confianza | 6 | RN-04 |
| U-05 `CorroboracionTest` | Confianza | 5 | RN-05 |
| U-06 `DetectorAnomaliasTest` | Confianza | 6 | RN-06 |
| U-07 `PoliticaReputacionTest` | Confianza | 4 | RN-07 |
| U-09 `SelectorPrecioVigenteTest` | Confianza | 5 | RN-09 |
| `EvaluadorConfianzaTest` | Confianza | 3 | RN-05, RN-06 |
| `PoliticaReemplazoVigenteTest` | Confianza | 9 | RN-07 |
| U-08 `LimiteAportesTest` | Precios | 4 | RN-08 |
| `RegistroPrecioTest` | Precios | 7 | RN-08, EC-07 |
| U-10 `OrdenComparacionTest` | Comparación | 4 | RN-10, AR-02 |
| `RedactorAdvertenciaTest` | Comparación | 7 | RN-04, RN-09 |

### 4.3 Frontend (Vitest)

| Archivo | Casos | Identificador | Requisito | Escenario / medida |
|---|---|---|---|---|
| `frontend/src/offline/colaReportes.test.ts` | 6 | I-09 (cliente) | RF-09 | EC-09 · M-12 (3 reportes offline sincronizados sin duplicados) |
| `frontend/src/utils/formato.test.ts` | 4 | — | RN-02 (redondeo solo al mostrar) | — |

### 4.4 Pruebas de arquitectura y análisis estático (CI)

| Prueba | Herramienta | Verifica | Resultado |
|---|---|---|---|
| AR-01 | Deptrac (`backend/deptrac.yaml`) | Cada contexto solo depende de los permitidos | 0 violaciones |
| AR-02 | Deptrac | Ningún contexto del núcleo depende de Comercial | 0 violaciones |
| Capas | Deptrac (`deptrac-capas.yaml`) | Domain no depende de Infrastructure ni de Http | 0 violaciones |
| M-14 | PHPStan nivel 8 | Hallazgos de análisis estático | 0 errores |
| M-13 | Pest + pcov `--min=80` | Cobertura del dominio | 97,9 % |

### 4.5 Pruebas planificadas que siguen pendientes

| Identificador | Qué falta | Corte |
|---|---|---|
| I-02 con reloj controlado | Medir la latencia cambio → retiro como vigente (M-02, EC-02) | 3 |
| S-09 | E2E en navegador con modo avión simulado (Playwright) | 3 |
| K-01 | Carga con k6: 500 usuarios, p95 ≤ 2 s (EC-04) | 3 |
| US-01, A-01, A-02 | Repetición de la prueba de usabilidad y reporte axe exportado (EC-01, EC-08) | 2–3 (evidencia del equipo) |
| Conjunto etiquetado de ≥ 200 reportes | Exhaustividad y falsos positivos de RN-06 (M-03, M-04) | 3 |
| C-03, C-12, C-19, C-24, I-04, I-07, I-23, I-25, I-26 | Endpoints aún planificados en `openapi.yaml` (`x-estado: planificado`) | 3 |

> **Nota:** la matriz se actualiza en cada corte. En el corte 3 se agregan las filas del módulo comercial (RF-C01…).

## 5. Declaración de uso de inteligencia artificial generativa

Conforme a la guía del proyecto (§12.5), el equipo declara que utilizó el asistente de IA generativa **Claude (Anthropic)** como apoyo en las siguientes actividades de este documento:

| Actividad | Uso de la IA | Revisión del equipo |
|---|---|---|
| Formato y estructura de la matriz (§1 y §2) | Generación de la tabla con las columnas RF → escenario → característica → medida → umbral → prueba → ADR | Verificación de cada fila contra los documentos fuente (especificación, plan de calidad, ADRs) |
| Nomenclatura de pruebas (U-, I-, C-, S-, K-, etc.) | Propuesta del esquema de identificadores | Validación de consistencia y completitud |
| Casos mínimos de prueba TDD (§3) | Sugerencia de casos borde; los casos que definen el comportamiento de negocio son del equipo | Revisión contra las reglas RN-01 a RN-10 |
| Relación de pruebas implementadas (§4, 2026-11-16) | Inventario de archivos de prueba y su correspondencia con RF, RN, EC y medidas | El equipo debe verificar que cada correspondencia es correcta antes de entregar |

**Compromisos del equipo:** la trazabilidad entre requisitos, escenarios y pruebas refleja decisiones de diseño del equipo. Ninguna relación de la matriz fue aceptada sin verificar que conecta correctamente el requisito con su evidencia de cumplimiento. Cada integrante está en capacidad de recorrer cualquier fila de la matriz y explicar por qué existe esa relación.


## Ampliación del corte 3 (Anexo 1: A1 Canasta comparada + B2 Entrega continua)

Los escenarios del Anexo se renumeran (EC-07 del Anexo → EC-11; EC-12 conserva su número) porque EC-07 a EC-10 ya existían. Ver [`analisis-impacto-ampliacion.md`](../03-arquitectura/ampliacion-corte3/analisis-impacto-ampliacion.md).

| RF | Descripción corta | Regla(s) | Escenario | Prueba(s) | Módulo |
|---|---|---|---|---|---|
| RF-27 | Armar una canasta de 1 a 20 productos con cantidad y unidad, sin cuenta (guardada en el dispositivo) | RN-11 (límite y validación) | EC-11, EC-07 | U-13, C-14 | Canasta (PWA) |
| RF-28 | Calcular el total por establecimiento con precios vigentes normalizados por unidad | RN-01, RN-02, RN-04, RN-09, RN-12 | EC-11 | U-11, C-14, K-02 | Canasta |
| RF-29 | Señalar por establecimiento los productos sin precio vigente, sin sumarlos como cero | RN-12 | EC-11 | U-11, C-14 | Canasta |
| RF-30 | Ordenar los establecimientos por un criterio declarado (`disponibilidad` o `total`) y mostrarlo | RN-10, RN-13 | EC-11, EC-06 | U-12, C-14, AR-02 | Canasta |

| Regla | Enunciado | Suite TDD |
|---|---|---|
| RN-11 | Una canasta tiene de 1 a 20 productos distintos; cada cantidad es > 0 y su unidad debe ser comparable con la unidad base del producto (RN-03) | U-13 `ValidadorCanasta` |
| RN-12 | Subtotal = menor precio por unidad base vigente del establecimiento × cantidad normalizada; el total suma solo los productos con precio vigente; los demás se listan como faltantes | U-11 `CalculadoraCanasta` |
| RN-13 | Orden `disponibilidad`: más disponibles → menor total → nombre. Orden `total`: menor total → más disponibles → nombre. Las promociones no alteran el total ni el orden | U-12 `OrdenCanasta` |

| Prueba | Tipo | Qué verifica |
|---|---|---|
| S-12 | Sistema (pipeline) | Un PR con una prueba rota y otro con una CVE CRITICAL corregible no llegan a staging (M-18) |
| S-13 | Sistema (pipeline) | Un cambio válido llega a staging y pasa la prueba de humo en ≤ 15 min (M-19) |
| S-14 | Sistema (operación) | Ensayo de reversión cronometrado (M-21) |

## Módulo comercial (corte 3, revisión 2 del análisis de impacto)

Ver [`analisis-impacto-modulo-comercial.md`](../03-arquitectura/analisis-impacto-modulo-comercial.md), ADR-0015, ADR-0022 y ADR-0023.

| RF | Descripción corta | Regla(s) | Escenario | Prueba(s) | Módulo |
|---|---|---|---|---|---|
| RF-31 | Solicitar, verificar y revocar el perfil comercial; credencial de comercio | RN-15 | EC-03, EC-07 | U-15, C-16 | Comercial |
| RF-32 | Publicar un precio oficial por el flujo de confianza | RN-05 a RN-07, RN-15 | EC-03, EC-06 | U-15, I-08, C-16 | Comercial → Precios |
| RF-33 | Publicar y retirar promociones en un bloque diferenciado | RN-14, RN-16 | EC-06, EC-11 | U-14, U-16, C-06, C-16 | Comercial → Comparación, Canasta |
| RF-34 | Solicitar una corrección que abre un caso de moderación | RN-15 | EC-03 | C-16 | Comercial → Moderación |

| Regla | Enunciado | Suite TDD |
|---|---|---|
| RN-14 | Validez de la promoción (tipo, valor, vigencia ≤ 31 días ⚑) | U-14 `ValidadorPromocion` |
| RN-15 | Solo un perfil verificado publica, y solo sobre su establecimiento | U-15 `PoliticaPublicacion` |
| RN-16 | Una promoción se muestra solo si el perfil está verificado, no fue retirada y está en vigencia; nunca en `items` ni en el total | U-16 `VisibilidadPromocion` |

| Prueba | Tipo | Qué verifica |
|---|---|---|
| C-16 | Contrato | Endpoints `/v1/comercial/*` y `/v1/moderacion/perfiles/*` según OpenAPI; 401 sin credencial, 403 sobre otro establecimiento; ninguna respuesta pública expone contacto ni credencial (EC-07) |
| C-06 (ampliada) | Contrato | Publicar una promoción no cambia `items` ni su orden en `GET /v1/comparaciones` (AR-02, M-09) |
| I-08 | Integración | Un precio oficial que difiere > 5 % de dos reportes recientes queda `POR_VERIFICAR` y abre un caso (ADR-0015, M-22) |
| AR-01 / AR-02 | Arquitectura | Deptrac: Comercial solo depende de `[Compartido, Catalogo, Establecimientos, Precios, Comparacion, Moderacion]`; ningún contexto depende de Comercial |
