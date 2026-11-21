# Análisis de impacto de la ampliación del corte 3 (Anexo 1): A1 Canasta comparada + B2 Entrega continua

> **Artefacto previo a la construcción** exigido por el Anexo 1, §3 regla 2 y §7. Se registra en el repositorio
> **antes del primer commit de implementación**; el historial de la rama `feat/ampliacion-a1-b2` lo evidencia
> (commit `docs:` previo a cualquier `test:` o `feat:`).
>
> - Fecha: 2026-11-21 · Plazo de la ampliación: dos semanas (hasta el 2026-12-05).
> - Se integra en: Arq. Entregables 5 y 6 · Cal. Entregables 5 y 6 · documento resumen · sustentación.
> - Relacionados: [ADR-0017](../adr/0017-canasta-contexto-nuevo-calculo-en-consulta.md),
>   [ADR-0018](../adr/0018-canasta-sin-persistencia-en-servidor.md),
>   [ADR-0019](../adr/0019-canasta-faltantes-orden-y-promociones.md),
>   [ADR-0020](../adr/0020-entrega-continua-staging-produccion-railway.md),
>   [ADR-0021](../adr/0021-quality-gate-sonarcloud-y-escaneo-trivy.md) y los escenarios EC-11 y EC-12
>   ([especificación de requisitos §8.2](../../01-requisitos/especificacion-requisitos.md)).

## 1. Opciones seleccionadas y razones

| Bloque | Opción | Razón |
|---|---|---|
| A | **A1. Canasta comparada** | Es una funcionalidad **que el equipo no conocía al diseñar el núcleo**, de modo que sirve como segunda medición honesta de EC-05 (Anexo §2). Reutiliza el modelo de lectura `comparacion_vigente`, la normalización (RN-01/RN-02), la vigencia (RN-04) y el selector (RN-09). Su núcleo son reglas de cálculo puras, adecuadas para el ciclo TDD con commits separados. |
| B | **B2. Entrega continua con controles de calidad** | Extiende el CI que ya funciona (Pest + cobertura + PHPStan + Deptrac + Spectral) hasta staging y producción en Railway, el proveedor de ADR-0013. La evidencia es directa: un cambio que rompe una prueba o introduce una vulnerabilidad crítica **no llega a staging**. |

**Opciones descartadas:**
- **A4:** diseñada desde el corte 1 (ADR-0006) y casi implementada (cola Dexie con `Idempotency-Key`). No mediría la evolución frente a algo desconocido.
- **A3:** se superpone con la corroboración (U-05) y la reputación (U-07) existentes.
- **A2:** choca con RN-08, que reemplaza el reporte del mismo producto, establecimiento y día, y depende de los precios oficiales del módulo comercial en construcción.
- **B1:** no hay instrumentación OpenTelemetry previa.
- **B3:** es la más grande (gateway, Circuit Breaker, ArchiMate, API de comercios); riesgo alto de no cumplir el mínimo en dos semanas.

## 2. Numeración de los escenarios

El Anexo numera sus escenarios EC-07 a EC-13 «continuando la sección 4.5 de la guía», que define EC-01 a EC-06. **En este proyecto EC-07 a EC-10 ya existen desde el corte 1** (privacidad, accesibilidad, sincronización offline y capacidad de prueba). Para no romper la trazabilidad, los escenarios se renumeran así:

| Escenario en el Anexo 1 | En este proyecto | Opción |
|---|---|---|
| EC-07 (canasta: p95 ≤ 3 s con 200 usuarios; sin sumar como cero) | **EC-11** | A1 |
| EC-12 (pipeline: bloquea el 100 %; staging ≤ 15 min sin pasos manuales) | **EC-12** (mismo número) | B2 |

La pregunta de diseño de A1 que menciona «EC-07» se responde frente a EC-11 y también frente a nuestro EC-07 (privacidad); ver §5.

## 3. Alcance comprometido (mínimo exigible)

### 3.1 A1 — Canasta comparada

| Mínimo exigible (Anexo §4) | Cómo se cumplirá | Verificación |
|---|---|---|
| Canasta de hasta 20 productos, con o sin cuenta | La canasta vive en el dispositivo (PWA) y se envía completa a `POST /v1/canastas/comparacion`. No requiere cuenta, porque Identidad está diferida (ADR-0014). Con 21 o más productos, la respuesta es 422 (ADR-0018). | U-13, C-14 |
| Total por establecimiento con precios normalizados por unidad de medida | Cada ítem declara una cantidad en g/kg, mL/L o unidad. Subtotal = precio por unidad base vigente × cantidad normalizada, con decimales exactos (RN-02). Si hay varias presentaciones en el establecimiento, se usa la de menor precio por unidad. | U-11, C-14 |
| Señalar los productos sin precio vigente, sin sumarlos como cero | Por establecimiento: lista `faltantes`, `disponibles` / `totalProductos` y `completa`. El total suma solo los disponibles (ADR-0019). | U-11, C-14, K-02 |
| Ordenar según criterios declarados | `criterioOrden` siempre presente. `disponibilidad` (por defecto: más disponibles, luego menor total) o `total` (menor total, luego más disponibles). Empate final por nombre: determinista y neutral (ADR-0019, ADR-0009). | U-12, C-14 |
| Reglas de cálculo mediante TDD | Suites U-11 `CalculadoraCanasta`, U-12 `OrdenCanasta` y U-13 `ValidadorCanasta`, con commits `test:` (rojo) → `feat:` (verde) → `refactor:` | Historial Git; informe de TDD |

La extensión opcional (combinación de dos establecimientos) solo se aborda con el mínimo completo y verificado (Anexo §3 regla 3).

### 3.2 B2 — Entrega continua con controles de calidad

| Mínimo exigible (Anexo §5) | Cómo se cumplirá | Verificación |
|---|---|---|
| Pipeline como código con construcción, pruebas, quality gate y escaneo de la imagen | `.github/workflows/cd.yml` reutiliza las etapas del CI y agrega el quality gate de **SonarCloud** y **Trivy** sobre las imágenes de `docker/railway/` (falla ante CRITICAL corregibles y ante secretos) (ADR-0021) | S-12 |
| Despliegue automático en staging y prueba de humo | Con todo en verde en `main`: `railway up --ci` al entorno **staging**, seguido de `pruebas-calidad/k6/humo-staging.js`. Si la prueba de humo falla, el pipeline queda en rojo y no hay promoción (ADR-0020). | S-13 |
| Promoción manual y procedimiento de reversión | Job `produccion` protegido con un *GitHub Environment* que exige aprobación. Runbook `runbook-reversion.md`: *rollback* de Railway sin reconstruir o redespliegue del commit anterior. Se ensaya y se cronometra. | S-14 |
| Secretos fuera del repositorio | `RAILWAY_TOKEN` (uno por entorno) y `SONAR_TOKEN` como secretos de GitHub Environments. `APP_KEY` y `DB_*` en las variables de Railway. `.env` en `.gitignore`, más un escaneo de secretos en el pipeline. | S-12 |

Extensión opcional (canary o blue-green): fuera del compromiso.

## 4. Componentes afectados y estimación (plantilla 8.3)

El esfuerzo estimado y el tipo de cambio se diligencian **antes de construir**. El esfuerzo real se llena al cierre (2026-12-05) y es la evidencia del contraste del Entregable 6 de Arquitectura (M-08: real / estimado ≤ 1,3).

### 4.1 A1 — Canasta comparada

| Componente | Tipo de cambio | Estimado (h) | Real (h) | Observaciones |
|---|---|---|---|---|
| Documentación previa (este análisis, ADR-0017 a 0019, EC-11) | Nuevo | 4 | | Precede a la implementación |
| `Domains/Canasta/Domain`: valores `ItemCanasta`, `OfertaProducto` y `ResultadoEstablecimiento`; servicios `CalculadoraCanasta` (U-11), `OrdenCanasta` (U-12) y `ValidadorCanasta` (U-13) | Nuevo (contexto delimitado) | 10 | | TDD rojo → verde → refactor |
| `Domains/Canasta/Application`: `CompararCanasta` y puerto `FuentePreciosVigentes` | Nuevo | 3 | | Sin acceso directo a la BD |
| `Domains/Canasta/Infrastructure`: adaptador del puerto | Nuevo | 2 | | Único contacto con Comparación |
| `Domains/Comparacion/Application`: servicio público `ConsultarPreciosVigentes` | Punto de extensión (clase nueva; `ConsultarComparacion` no se modifica) | 3 | | Único cambio en el núcleo; cuenta para M-07 |
| `Domains/Compartido` (`Dinero`, `NormalizadorUnidad`, `Comparabilidad`) | Sin cambio | 0 | | Reutilizados |
| `Domains/Confianza` (`PoliticaVigencia`, `SelectorPrecioVigente`) | Sin cambio | 0 | | Reutilizados a través de Comparación |
| Precios, Catálogo, Establecimientos, Moderación, Identidad, Ingesta | Sin cambio | 0 | | |
| Comercial (en paralelo) | Sin cambio | 0 | | La canasta no lo lee (AR-02) |
| `app/Http`: `CanastaController`, `CompararCanastaRequest` y ruta | Nuevo | 3 | | Problem Details (RFC 9457) |
| `deptrac.yaml`: capa `Canasta: [Compartido, Comparacion]` | Modificado (configuración) | 0,5 | | AR-01/AR-02 en 0 violaciones |
| `api/openapi.yaml` 0.3.0: `SolicitudCanasta` y `ResultadoCanasta` | Modificado | 2 | | Spectral en el CI |
| `tests/Feature/Api/V1/CanastaTest.php` (C-14, C-15) | Nuevo | 3 | | Incluye 0 datos personales (EC-07) |
| Frontend: store `canasta` en el dispositivo, `CanastaView.vue`, ruta, navegación y `api/canasta.ts` | Nuevo | 10 | | Faltantes anunciados en texto, no solo con color (EC-08) |
| Prueba de carga K-02 (k6, 200 VU, canastas de 20) y resultados | Nuevo | 4 | | EC-11 / M-16, M-17 |
| **Subtotal A1** | | **44,5** | | |

### 4.2 B2 — Entrega continua con controles de calidad

| Componente | Tipo de cambio | Estimado (h) | Real (h) | Observaciones |
|---|---|---|---|---|
| Documentación previa (ADR-0020, ADR-0021, EC-12) | Nuevo | 3 | | Precede a la implementación |
| `.github/workflows/ci.yml` | Modificado (reutilizable con `workflow_call`) | 1 | | Las etapas actuales no cambian |
| Quality gate SonarCloud (`sonar-project.properties`, cobertura de pcov) | Nuevo | 3 | | El equipo crea el proyecto en SonarCloud |
| Escaneo Trivy (imágenes api y web + secretos) | Nuevo | 2 | | |
| Entornos `staging` y `production` en Railway (api, worker, web y PostGIS) | Nuevo (infraestructura) | 4 | | Panel de Railway, una sola vez; documentado |
| `.github/workflows/cd.yml`: staging + prueba de humo | Nuevo | 4 | | |
| `pruebas-calidad/k6/humo-staging.js` | Nuevo | 1,5 | | 0 errores; p95 ≤ 2 s |
| Promoción manual a producción (*GitHub Environment* con revisor) | Nuevo | 1,5 | | |
| Runbook de reversión y ensayo cronometrado | Nuevo | 3 | | S-14 |
| Gestión de secretos | Nuevo (configuración) | 1 | | |
| Evidencia EC-12: PR que rompe una prueba, PR con una vulnerabilidad crítica, tiempo hasta staging | Nuevo | 3 | | En `docs/02-calidad/evidencias/` |
| Código de la aplicación | Sin cambio | 0 | | B2 no toca el producto |
| **Subtotal B2** | | **27** | | |
| **Total de la ampliación** | | **71,5** | | ≈ 24 h por integrante |

**Supuestos:** tres integrantes con unas 12 h por semana cada uno. La estimación del módulo comercial sirve de referencia, y su contraste también se reporta en el Entregable 6.

## 5. Preguntas de diseño abiertas: respuestas del equipo

### A1
1. **¿Cómo se compara una canasta si un establecimiento no tiene precio vigente para un producto?**
   El producto va a `faltantes` de ese establecimiento y **no se suma**: no cuenta como cero ni se estima. El criterio por defecto (`disponibilidad`) pone primero a quienes tienen más productos y solo después considera el total. Así, un establecimiento al que le faltan productos no aparece como «el más barato» (ADR-0019).
2. **¿El total se calcula en cada consulta o se precalcula? ¿Qué implica frente a EC-02 y EC-07?**
   **En cada consulta**, sobre `comparacion_vigente`, que ya está precalculado por presentación y establecimiento (ADR-0003):
   - **Actualidad (EC-02):** la canasta hereda la latencia del modelo de lectura (≤ 5 min), sin una segunda proyección.
   - **Desempeño (EC-11, el EC-07 del Anexo):** con máximo 20 productos, el cálculo es una lectura indexada por `producto_id` más un cálculo en memoria acotado (productos × establecimientos). Se mide con K-02.
   - **Privacidad (EC-07 nuestro):** no persistir canastas evita registrar hábitos de compra (ADR-0017, ADR-0018).
3. **¿Cómo se aplican las promociones «pague X lleve Y» a las cantidades de la canasta?**
   **No se aplican al total ni al orden.** El total usa solo precios comparables. Cuando el módulo comercial publique promociones, irán al bloque diferenciado `contenidoPromocional`, con el ahorro potencial calculado aparte. Mezclarlas haría que el contenido comercial alterara el orden, lo que viola ADR-0009, AR-02 y la consideración ética del Anexo (ADR-0019).

### B2
1. **¿Qué umbrales tiene el quality gate y cómo se relacionan con el plan de calidad?**
   Bloquean el pipeline:
   - Cobertura ≥ 80 % (M-13).
   - PHPStan nivel 8 sin errores y 0 hallazgos críticos o bloqueantes nuevos en SonarCloud (M-14).
   - Deptrac sin violaciones (M-07).
   - 0 vulnerabilidades CRITICAL corregibles y 0 secretos (M-20).
   - Contrato OpenAPI válido.

   La tabla completa está en ADR-0021.
2. **¿Cómo se revierte un despliegue defectuoso y en cuánto tiempo?**
   Con el *rollback* de Railway, que restaura la imagen anterior sin reconstruir (retención de 72 h en Hobby). La alternativa es redesplegar el commit sano anterior. Meta: ≤ 5 min (M-21), medida en un ensayo (`runbook-reversion.md`).
3. **¿Staging puede reconstruirse desde cero sin pasos manuales?**
   El código y las migraciones sí: la API migra al arrancar y el pipeline despliega desde el repositorio. Además, la topología de servicios queda declarada en `.railway/railway.ts` (IaC de Railway; revisión de ADR-0020), de modo que staging se recrea con `railway config apply`; solo los secretos se cargan aparte. Después, cada despliegue es automático (EC-12).

## 6. Segunda medición de EC-05

| Medida | Cómo se mide | Meta |
|---|---|---|
| M-07 Cambios estructurales al núcleo | Diff de la rama frente a `main` en `Domains/{Precios,Confianza,Comparacion,Catalogo,Establecimientos,Compartido}`, más Deptrac en verde | 0 (solo la clase nueva `ConsultarPreciosVigentes`) |
| M-08 Precisión de la estimación | Σ real / Σ estimado de §4, por opción y en total | ≤ 1,3 |

Si no se cumple, el Entregable 6 documenta la causa y la decisión que se tomaría de nuevo (Anexo §3 regla 4).

## 7. Riesgos, puntos de sensibilidad y trade-offs iniciales

| Tipo | Descripción | Mitigación |
|---|---|---|
| Riesgo | El costo de dos entornos completos en Railway supera el crédito del plan | Reposo automático en staging; si no alcanza, staging en Multivac con el mismo pipeline (alternativa en ADR-0020) |
| Riesgo | Dependencia de servicios gratuitos (SonarCloud, Railway, Actions) | Gratuitos para repositorios públicos; PHPStan y Deptrac siguen bloqueando sin SonarCloud |
| Riesgo | Railway construye su propia imagen; no es el mismo artefacto que escanea Trivy | Mismo Dockerfile y mismo commit, con imágenes base fijadas por versión (trade-off en ADR-0020) |
| Sensibilidad | Latencia de la canasta frente a establecimientos × productos | Límite de 20 productos, filtro por municipio; K-02 con ≥ 300 establecimientos |
| Sensibilidad | Unidad pedida frente a la unidad base del producto | `ValidadorCanasta` rechaza unidades no comparables (RN-03) con 422 |
| Trade-off | Cálculo en consulta (actualidad, simplicidad) frente a precálculo (latencia mínima) | Se prefieren actualidad y privacidad; K-02 verifica la latencia |
| Trade-off | Canasta en el dispositivo (privacidad, sin cuenta) frente a canasta en el servidor (multidispositivo) | Se prefiere la privacidad; la sincronización espera a Identidad (ADR-0014) |

## 8. Declaración de uso de IA generativa

Redactado con apoyo de Claude (Anthropic) a partir del Anexo 1, del código y de la documentación del repositorio. Las opciones, las respuestas y las estimaciones fueron revisadas por el equipo, que puede explicarlas en la sustentación (guía §12.5).
