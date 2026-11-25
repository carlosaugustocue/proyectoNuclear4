# Informe de evaluación de la calidad (ISO/IEC 25040 y 25012)

> Corte 3 · Modelos de Calidad · **Entregable 5**. Fecha de ejecución: 2026-11-25 · commit evaluado `f4c84a7` (`main`).
> Responsable de la evaluación: Sofía Giraldo González (plan de calidad §10). Apoyo en pruebas y CI: Carlos Aranzazu.
> Insumos: [plan de aseguramiento](plan-aseguramiento-calidad.md) (modelo, medidas y umbrales), [especificación de
> requisitos](../01-requisitos/especificacion-requisitos.md) (escenarios), [matriz de trazabilidad](../01-requisitos/matriz-trazabilidad.md)
> y [análisis de impacto de la ampliación](../03-arquitectura/ampliacion-corte3/analisis-impacto-ampliacion.md) (EC-11, EC-12).

## Resumen ejecutivo

Se evaluó el sistema integrado (núcleo, módulo Comercial y la ampliación A1 + B2) con el proceso de
**ISO/IEC 25040:2024**, las medidas M-01 a M-22 del plan y el modelo de datos de **ISO/IEC 25012**.
De 22 medidas, **13 cumplen**, **1 cumple con reservas**, **5 no cumplen** y **3 no se pudieron medir**.

| Resultado | Medidas |
|---|---|
| ✅ Cumplen (objetivo o aceptable) | M-02, M-03, M-07, M-09, M-10, M-11, M-12, M-13, M-14, M-15, M-17, M-18, M-20 · datos D-01 a D-04 y D-06 |
| 🟡 Cumple con reservas | M-19 (mediana 4 min 48 s; 3 de 10 despliegues pasaron de 15 min por la cola del CD) |
| ❌ No cumplen | **M-01** usabilidad (62,5 %), **M-04** falsos positivos (peor semilla 7,5 %), **M-05/M-06** carga de consultas (p95 26,4 s; 12,9 % de errores), **M-16** carga de la canasta (p95 8,4 s) |
| ⏳ Sin medir | **M-08** (faltan las horas reales del equipo), **M-21** (ensayo de reversión pendiente), **M-22**/D-05 (sin precios oficiales reales) |

**Conclusión:** el producto es **funcionalmente correcto, seguro, accesible y mantenible**. Los datos que publica
son actuales, trazables y no exponen a los reportantes. Queda **una brecha de desempeño**: el servidor de
aplicaciones (`artisan serve`) se satura antes de 500 usuarios. Esa brecha explica M-05, M-06 y M-16, y su
tratamiento está identificado (§6, I-13). La credibilidad (M-04) y la usabilidad (M-01) tienen planes de mejora
concretos. El sistema **es apto para la demostración y para un piloto con tráfico bajo**, pero no para la carga
de EC-04 hasta aplicar el tratamiento.

---

## 1. Definir la evaluación (ISO/IEC 25040, 5.2)

| Elemento | Definición |
|---|---|
| Propósito | Decidir si el sistema integrado cumple los requisitos de calidad acordados en el plan, y priorizar mejoras para el cierre |
| Objeto evaluado | API Laravel (11 contextos), worker, PWA, base de datos y pipeline de entrega continua; datos colaborativos del modelo de lectura |
| Modelo de calidad | ISO/IEC 25010:2023 (producto) e ISO/IEC 25012 (datos), según el plan §3 y §4 |
| Características priorizadas | Adecuación funcional, eficiencia de desempeño, capacidad de interacción, seguridad, mantenibilidad (altas); fiabilidad, flexibilidad, compatibilidad (medias) |
| Partes interesadas | Consumidores, reportantes, comercios, moderadores; docente evaluador |
| Rigor | Mediciones automatizadas y reproducibles desde el repositorio. Las mediciones manuales (usabilidad) usan el protocolo del corte 2 |

## 2. Diseñar la evaluación (5.3)

Las medidas, funciones de medición y umbrales son los del plan §5 (M-01 a M-22) y la auditoría D-01 a D-06 para
los datos. **Criterio de decisión por medida:** se compara con el umbral *aceptable*. Si el resultado alcanza el
*objetivo*, se reporta como tal. En las medidas con varias corridas (M-03, M-04), decide el **peor caso**.
**Criterio global:** el producto se acepta para la demostración si no hay medidas incumplidas en seguridad ni en
corrección funcional, y si cada incumplimiento tiene un tratamiento registrado.

## 3. Planificar la evaluación (5.4)

| Nivel (plan §6) | Herramienta | Dónde corre | Datos |
|---|---|---|---|
| Unitarias TDD (U-01 a U-19) | Pest | CI en cada PR | Casos de prueba |
| Integración y contrato (I-xx, C-xx) | Pest + PostgreSQL 16/PostGIS | CI en cada PR | Base efímera `nuclear_test` |
| Sistema y accesibilidad (S-08 a S-15, A-01) | Playwright 1.49 + axe-core | CI en cada PR, contra las imágenes de Railway | Semilla de demostración + catálogo SIPSA |
| Carga (K-01, K-02) y actualidad (I-02) | k6 (`grafana/k6`) | `pruebas-calidad/ejecutar.sh`, entorno aislado con las imágenes de producción | 2.000 presentaciones, 300 establecimientos (sintéticos) |
| Credibilidad (EC-03) | `php artisan calidad:deteccion` | Mismo entorno aislado | 250 reportes etiquetados × 5 semillas |
| Calidad de datos (D-01 a D-06) | `php artisan calidad:auditar-datos` | Entorno de desarrollo (126 productos, 737 observaciones) | Datos de demostración + SIPSA |
| Entrega continua (EC-12) | GitHub Actions, Trivy, SonarCloud | Ejecuciones reales del CD y dos PR de prueba | Historial del repositorio |

**Entorno de las mediciones de carga:** Intel Core i7-6500U (4 hilos), 11,6 GB de RAM, Docker 29.8. La API corre
con la imagen de producción (`artisan serve`, 4 procesos PHP) y PostgreSQL en el mismo equipo. **No es Railway**:
los tiempos absolutos cambian con el hardware, pero la forma de la saturación sí corresponde a la configuración
de producción.

## 4. Ejecutar la evaluación (5.5): resultados del producto (ISO/IEC 25010)

### 4.1 Pruebas de integración, sistema y carga (resumen)

| Nivel | Resultado | Evidencia |
|---|---|---|
| Unitarias + integración + contrato | **375 casos en verde** (1.110 aserciones), 29 s | CI de `main`, ejecución 45667564675 |
| Cobertura | **91,6 % total**; capa de dominio 99,6 % (promedio por archivo, 98 archivos) | Mismo job (pcov, `--min=80`) |
| Sistema y accesibilidad | **17/17** pruebas Playwright; axe con **0 violaciones** A/AA en 17 auditorías de 12 pruebas | Job «Sistema y accesibilidad»; `e2e/resultados/axe/resumen.md` |
| Carga de consultas K-01 | p95 26,4 s · 12,9 % de errores · 38,5 pet/s | `pruebas-calidad/resultados/2026-11-25_1315/` |
| Carga de la canasta K-02 | p95 8,4 s · 0 % de errores · M-17 = 5264/5264 | Mismo directorio |
| Actualidad I-02 | p50 1,09 s · p95 1,35 s · 0 cambios sin reflejar | Mismo directorio |

### 4.2 Resultados por característica y medida

| Característica (25010) | Medida | Resultado | Umbral aceptable | Veredicto | Evidencia |
|---|---|---|---|---|---|
| **Adecuación funcional** — corrección | M-09 neutralidad | 1 (U-10, C-06, AR-02 en verde; la ingesta SIPSA no altera la comparación) | 1 | ✅ | Pest; `IngestaSipsaTest` |
| | M-17 faltantes de la canasta | 5264/5264 respuestas correctas bajo carga | 1 | ✅ objetivo | K-02 |
| **Eficiencia de desempeño** — comportamiento temporal | M-02 latencia de actualidad | p95 1,35 s | ≤ 5 min | ✅ objetivo | I-02 |
| | M-05 p95 consultas (500 VU) | **26,43 s** | ≤ 2 s | ❌ | K-01 |
| | M-06 tasa de error (500 VU) | **12,94 %** | < 1 % | ❌ | K-01 |
| | M-16 p95 canasta (200 VU) | **8,43 s** | ≤ 3 s | ❌ | K-02 |
| **Capacidad de interacción** — operabilidad | M-01 registros ≤ 15 s | 0,625 (5/8), medido en el corte 2 | ≥ 0,80 | ❌ | [Protocolo de usabilidad](protocolo-prueba-usabilidad.md) |
| — inclusividad | M-11 violaciones axe | 0 en 17 auditorías (12 pruebas) | 0 | ✅ | A-01 |
| **Seguridad** — integridad, resistencia | M-03 exhaustividad (peor de 5) | 0,860 (media 0,924) | ≥ 0,80 | ✅ aceptable | `calidad:deteccion` |
| | M-04 falsos positivos (peor de 5) | **0,075** (media 0,032) | ≤ 0,05 | ❌ | Ídem |
| — confidencialidad | M-10 datos personales en respuestas públicas | 0 (C-07) | 0 | ✅ | Pest, S-15 |
| — integridad del artefacto | M-20 CVE CRITICAL corregibles + secretos | 0 en `main` (3 detectados y bloqueados en el PR de prueba #28) | 0 | ✅ | Trivy |
| **Mantenibilidad** — modularidad | M-07 cambios estructurales al núcleo | 0 (ver la evaluación de arquitectura §5) | 0 | ✅ | `git diff`, Deptrac |
| — capacidad de ser probado | M-13 cobertura del dominio | 0,996 | ≥ 0,80 | ✅ objetivo | CI |
| | M-14 hallazgos críticos de análisis estático | 0 (PHPStan nivel 8; quality gate de SonarCloud aprobado) | 0 | ✅ | CI |
| **Fiabilidad** — tolerancia a fallos | M-12 sincronización offline | 3/3 sincronizados, 0 duplicados (S-09) | 1 | ✅ | e2e |
| — disponibilidad | M-21 tiempo de reversión | **No medido** | ≤ 5 min | ⏳ | Runbook §4 vacío |
| **Calidad del dato** | M-15 vencidos mostrados como vigentes | 0/521 (D-01) | 0 | ✅ | §5 |
| | M-22 precios oficiales no confirmados | Sin datos (D-05) | ≤ 0,10 | ⏳ | §5 |
| **Calidad del proceso de entrega** (EC-12) | M-18 cambios defectuosos bloqueados | **2/2** (PR #27: defecto en RN-05 → 3 pruebas en rojo; PR #28: imagen vulnerable → Trivy en rojo) | 1 | ✅ | Ejecuciones 45673649579 y 45673663667 |
| | M-19 tiempo hasta staging | Mediana **4 min 48 s**; 7/10 ≤ 15 min | ≤ 15 min | 🟡 | Tabla 4.3 |
| **Estimación del cambio** (EC-05) | M-08 real / estimado | **Sin medir** | ≤ 1,3 | ⏳ | Evaluación de arquitectura §5 |

### 4.3 Detalle de M-19 (tiempo desde el push a `main` hasta el humo de staging en verde)

| Commit | Push (UTC) | Staging en verde | Duración | ≤ 15 min |
|---|---|---|---|---|
| `06fab52` | 21-11 21:51:45 | 21:55:38 | 3 min 53 s | ✅ |
| `f0d4350` | 22-11 01:54:32 | 01:59:04 | 4 min 32 s | ✅ |
| `17a6442` | 22-11 01:55:54 | 02:12:21 | 16 min 27 s | ❌ en cola |
| `9a4bbd1` | 22-11 01:58:20 | 02:22:26 | 24 min 06 s | ❌ en cola |
| `acdb1cd` | 22-11 02:43:21 | 02:47:35 | 4 min 14 s | ✅ |
| `82bc12c` | 22-11 16:27:53 | 16:47:41 | 19 min 48 s | ❌ en cola |
| `c4f5d12` | 22-11 16:53:23 | 16:58:19 | 4 min 56 s | ✅ |
| `2be64a3` | 25-11 16:15:14 | 16:20:00 | 4 min 46 s | ✅ |
| `b4f1ebb` | 25-11 17:02:38 | 17:09:38 | 7 min 00 s | ✅ |
| `f4c84a7` | 25-11 17:42:25 | 17:47:13 | 4 min 48 s | ✅ |

Los tres casos fuera de umbral **no son lentitud del pipeline**: el grupo de concurrencia `cd-main` serializa las
ejecuciones, y una ejecución que espera la **aprobación manual de producción** retiene la cola. Las siguientes
no llegan a staging hasta que alguien aprueba. Ver la incidencia I-14.

### 4.4 Escenarios de la ampliación (Anexo 1)

| Escenario | Medida | Resultado | Veredicto |
|---|---|---|---|
| EC-11 canasta comparada (A1) | M-16 p95 ≤ 3 s con 200 VU | 8,43 s | ❌ (era 25,2 s antes de la corrección N+1, I-06) |
| | M-17 faltantes señalados | 1 | ✅ |
| | Reglas de la canasta por TDD | U-11 a U-13 (26 pruebas, commits rojo → verde → refactor) | ✅ |
| EC-12 entrega continua (B2) | M-18 | 2/2 bloqueados | ✅ |
| | M-19 | Mediana 4 min 48 s | 🟡 |
| | M-20 | 0 en `main` | ✅ |
| | M-21 | Sin medir | ⏳ |

## 5. Evaluación de los datos (ISO/IEC 25012)

Auditoría de solo lectura `php artisan calidad:auditar-datos` (prueba de integración `AuditoriaCalidadDatosTest`,
10 casos). Ejecutada el 2026-11-25 sobre el entorno de desarrollo: 126 productos (12 de la semilla + 114 nuevos
de SIPSA), 23 establecimientos y 737 observaciones. Evidencia: [`evidencias/datos/auditoria-datos-2026-11-25.json`](evidencias/datos/auditoria-datos-2026-11-25.json).

| Id | Característica (25012) | Función de medición | Resultado | Umbral | Veredicto |
|---|---|---|---|---|---|
| D-01 (M-15) | Actualidad | Precios mostrados como vigentes fuera de su plazo sin advertencia / mostrados | **0/521** | 0 | ✅ |
| D-02 | Completitud | Presentaciones con cantidad, unidad y unidad base / total | **130/130** | 1 | ✅ |
| D-03 | Trazabilidad | Observaciones con fuente, fecha de observación y de registro / total | **737/737** | 1 | ✅ |
| D-04 | Consistencia | Productos duplicados (nombre normalizado sin tildes + marca) / total | **0/126** | ≤ 0,02 | ✅ |
| D-05 (M-22) | Credibilidad del precio oficial | Precios `COMERCIO` no CONFIABLE / precios `COMERCIO` | Sin datos | ≤ 0,10 | — |
| D-06 (M-10) | Confidencialidad | Columnas con IP, *user agent* o coordenadas fuera de establecimientos / columnas revisadas | **0/23** | 0 | ✅ |

**Lectura:**
- **D-04** se mantiene en 0 después de cargar SIPSA porque la ingesta **reutiliza el genérico existente**: «Tomate
  chonto» no se duplicó. Esto lo verifica la prueba I-25.
- **D-06** dio 1 hallazgo en su primera ejecución: la tabla `sessions` de Laravel guardaba IP y *user agent*. Se
  corrigió antes de este informe (I-12).
- **D-05** no tiene datos porque ningún comercio real ha publicado aún. La regla está cubierta por la prueba I-08,
  pero la medida se debe tomar en producción después del piloto.
- **Exactitud** (¿el precio coincide con la góndola?) y **credibilidad del dato colaborativo** no se pueden auditar
  sin trabajo de campo. Esa limitación está declarada en ADR-0007 y ADR-0010. Los precios de los productos SIPSA en
  tiendas son **datos de demostración supuestos** (fuente `EQUIPO_SEMILLA`), no observaciones.

## 6. Concluir la evaluación (5.6): registro de incidencias y su tratamiento

Las incidencias provienen de las pruebas y mediciones de los cortes 2 y 3. «Escapó» indica que llegó a `main`
o a producción antes de detectarse.

| Id | Fecha | Incidencia | Cómo se detectó | ¿Escapó? | Tratamiento | Estado |
|---|---|---|---|---|---|---|
| I-01 | 16-11 | `brick/money` en `require-dev`: la imagen de producción daba 500 al registrar o comparar | Mediciones de carga con la imagen real | Sí, a `main` | Pasó a `require` (`67fa87f`) | ✅ Cerrada |
| I-02 | 16-11 | `PHP_CLI_SERVER_WORKERS` no tenía efecto (faltaba `--no-reload`) | Mediciones K-01 | Sí, a `main` | `b7005bc` | ✅ Cerrada |
| I-03 | 16-11 | El CI no recorría el flujo de eventos (cola asíncrona en pruebas) | Revisión del CI | No | Cola síncrona en `TestCase` (`834583b`) | ✅ Cerrada |
| I-04 | 21-11 | Pest en el contenedor de desarrollo vaciaba la base `nuclear` | Datos de demostración desaparecidos | No (local) | Las pruebas fuerzan `nuclear_test` (`2e2dd2b`) | ✅ Cerrada |
| I-05 | 21-11 | 3 CVE CRITICAL corregibles en `nginx:1.27-alpine` | Trivy (M-20) | No | `nginx:1.30-alpine` + `apk upgrade` (`2962adb`) | ✅ Cerrada |
| I-06 | 21-11 | Canasta con consultas N+1: p95 25,2 s | K-02 | No | Unidades base en una sola consulta (`82443ec`): p95 9,9 s → 8,4 s | 🟡 Mitigada (ver I-13) |
| I-07 | 21-11 | Primer CD falló: «railway: not found» (CLI instalada en paralelo con npx) | CD | Sí, al pipeline (bloqueó producción) | Instalar la CLI una vez (`f4048e1`, PR #15) | ✅ Cerrada |
| I-08 | 21-11 | En Railway todas las peticiones parecían venir de la misma IP: el límite por IP afectaba a todos | Revisión en producción | Sí, a producción | IP real tras el proxy, cabeceras y límites (`d1d53c9`, ADR-0024) | ✅ Cerrada |
| I-09 | 21-11 | «Sigue igual» respondía 409 cuando había un reporte más reciente no publicado | Prueba de sistema S-10 | No | Confirmar compara contra el precio publicado (`4c329ee`) | ✅ Cerrada |
| I-10 | 22-11 | El PR #21 se fusionó en una rama intermedia y su código no llegó a `main`; el CI solo corre en PR a `main` | Revisión de `main` tras la fusión | Sí, se perdió el cambio | PR #22 repuso el código; regla: PR siempre contra `main` | ✅ Cerrada |
| I-11 | 25-11 | Contraste insuficiente en navegación y textos auxiliares; `radiogroup` con `<li>` | axe en el CI (A-01) | No | `c938e8a` | ✅ Cerrada |
| I-12 | 25-11 | La tabla `sessions` de Laravel guardaba IP y *user agent* (R-01) | Auditoría D-06 | Sí, a `main` (sin uso real) | Sesiones `array` y migración que elimina la tabla (`6cd495f`) | ✅ Cerrada |
| I-13 | 16-11 → 25-11 | **EC-04 y EC-11 no se cumplen**: `artisan serve` (servidor de desarrollo de PHP) se satura; con 500 VU, p95 26,4 s y 12,9 % de errores | K-01, K-02 | — | **Pendiente:** servir la API con php-fpm + nginx o FrankenPHP (proceso persistente), índices de cobertura para la canasta y repetir K-01/K-02 con el generador de carga en otra máquina. Responsable: Miguel (arquitectura) | ❌ Abierta |
| I-14 | 22-11 | Despliegues a staging esperando más de 15 min (M-19) | Marcas de tiempo del CD | — | Separar el grupo de concurrencia de staging del de producción, para que una aprobación pendiente no retenga staging | ❌ Abierta |
| I-15 | 16-11 → 25-11 | M-04 = 7,5 % en la peor semilla: descuentos legítimos marcados como anomalías | `calidad:deteccion` | — | Recalibrar RN-06 con datos reales (umbral MAD y ventana); registrar «descuento» como causa en la moderación. Bloqueado por la Fase 1 (ADR-0007) | ❌ Abierta |
| I-16 | 11-11 | M-01 = 62,5 %: el registro supera 15 s para 3 de 8 participantes | Prueba con usuarios (corte 2) | — | Rediseño del registro (acciones fijas, búsqueda con filtro, PR #26); **repetir la prueba con ≥ 8 usuarios** | 🟡 Tratada, sin re-medir |
| I-17 | 25-11 | Elegir un resultado antes de 300 ms devolvía al usuario a la búsqueda | S-15 en el PR #26 | No | El temporizador se cancela al salir de la vista (`f3a9f0e`) | ✅ Cerrada |
| I-18 | 25-11 | La caché de opcache del entorno de desarrollo sirve rutas viejas | Captura de pantalla con 404 | No (local) | Reiniciar `api` tras cambiar rutas; documentado | ✅ Cerrada (procedimiento) |

**Indicadores de las incidencias:** 18 registradas, 12 cerradas, 1 cerrada como procedimiento, 1 mitigada, 1 tratada sin re-medir y 3 abiertas. **Seis escaparon** a `main`, a producción o al pipeline (I-01, I-02, I-07, I-08, I-10, I-12). Cinco de esas seis se detectaron por **mediciones, auditorías o la operación**, no por las pruebas funcionales. Esa es la razón principal para mantener K-01/K-02 y la auditoría de datos como parte del proceso (ver el Entregable 6).

## 7. Trazabilidad

La [matriz de trazabilidad](../01-requisitos/matriz-trazabilidad.md) enlaza requisitos → escenarios → pruebas.
Este informe agrega el resultado de cada escenario: EC-01 ❌ (M-01), EC-02 ✅, EC-03 🟡 (M-03 ✅, M-04 ❌), EC-04 ❌,
EC-05 ✅ en M-07 y ⏳ en M-08, EC-06 ✅, EC-07 ✅, EC-08 ✅, EC-09 ✅, EC-10 ✅ (el PR #27 demuestra que la suite
falla antes del merge), EC-11 🟡 (M-16 ❌, M-17 ✅), EC-12 🟡 (M-21 sin medir).

## 8. Tareas que solo puede hacer el equipo

1. **M-21:** ensayar el runbook de reversión en staging (procedimientos A y B) y llenar la tabla del runbook §4.
2. **M-08:** registrar las horas reales por componente en los dos análisis de impacto.
3. **M-01:** repetir la prueba de usabilidad con ≥ 8 personas sobre la PWA rediseñada.
4. **Producción:** aprobar la promoción pendiente del #25. Hoy producción muestra 12 productos y staging 126.

## 9. Declaración de uso de inteligencia artificial generativa

Las mediciones, la auditoría y la redacción se hicieron con apoyo de Claude (Anthropic). Las cifras provienen
de ejecuciones reproducibles citadas en cada fila. El equipo debe revisarlas y poder defender su interpretación
en la sustentación (guía §12.5).
