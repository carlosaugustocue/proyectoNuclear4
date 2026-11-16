# Informe TDD y análisis estático — Corte 2

> **Entregable 3 de Modelos de Calidad (Corte 2)** · Fecha: 2026-11-11.
> Actualizado el 2026-11-16 (§11: cobertura medida y nuevos ciclos TDD con commits separados).
> Evidencia del ciclo rojo-verde-refactor y del análisis estático aplicados a las reglas de negocio RN-01 a RN-10.

---

## 1. Objetivo

Demostrar que las 10 reglas de negocio del dominio de precios fueron implementadas siguiendo el ciclo TDD (Beck, 2003) y que el código resultante pasa el análisis estático PHPStan en nivel 8 con 0 errores.

## 2. Herramientas utilizadas

| Herramienta | Versión | Propósito |
|---|---|---|
| Pest PHP | 3.x | Framework de pruebas unitarias (wrapper expresivo sobre PHPUnit) |
| PHPStan + Larastan | 2.x / 3.x | Análisis estático (nivel 8 — máximo rigor) |
| brick/money + brick/math | 0.10 / 0.12 | Aritmética decimal exacta sin floats (RN-02) |
| Docker Compose | 2.x | Entorno reproducible para ejecutar `make test` y `make lint` |

## 3. Ciclo TDD aplicado

El proyecto sigue la convención de commits definida en el plan de calidad (6):

1. **`test:`** — se escribe la prueba que falla (rojo)
2. **`feat:`** — se escribe el mínimo código para pasar (verde)
3. **`refactor:`** — se limpia sin romper pruebas

### 3.1 Evidencia en el historial de Git

| Commit | Prefijo | Descripción | PR |
|---|---|---|---|
| `f6bd9b2` | `feat` | Núcleo funcional del dominio Precios con TDD (entidad, VOs, tests) | #3 |
| `76bb34d` | `feat` | Listener queued `PublicarPrecioRegistrado` + PHPStan nivel 8 verde | #4 |
| `c2747ed` | `test` | Suites TDD U-01 a U-10 — 10 servicios de dominio con 52 pruebas | #5 |

> **Nota:** en el PR #5, las 10 suites de prueba se escribieron primero (rojo), luego se implementó cada servicio hasta que pasaran (verde), y finalmente se corrigió PHPStan nivel 8 hasta lograr 0 errores (refactor). El commit único agrupa el ciclo completo por pragmatismo de un equipo de 3 personas, pero el flujo interno fue estrictamente rojo-verde-refactor.

### 3.2 Flujo de trabajo por PR

```
main ──┬── chore/skeleton-docker ──── PR #2 (merge) ──┐
       ├── feat/dominio-precios ───── PR #3 (merge) ──┤
       ├── feat/worker-outbox ─────── PR #4 (merge) ──┤
       └── feat/suites-tdd-u01-u10 ── PR #5 (merge) ──┘── main (actual)
```

Cada PR requirió revisión de al menos 1 integrante distinto del autor antes de mergearse a `main`.

## 4. Suites de prueba implementadas

### 4.1 Resumen cuantitativo

| Suite | Clase bajo prueba | Regla | Tests | Líneas test | Líneas impl. |
|---|---|---|---|---|---|
| U-01 | `NormalizadorUnidad` | RN-02 | 8 | 56 | 41 |
| U-02 | `Dinero` | RN-02 | 4 | 42 | 81 |
| U-03 | `Comparabilidad` | RN-01 | 6 | 36 | 41 |
| U-04 | `PoliticaVigencia` | RN-04 | 6 | 51 | 36 |
| U-05 | `Corroboracion` | RN-05 | 5 | 65 | 51 |
| U-06 | `DetectorAnomalias` | RN-06 | 6 | 49 | 81 |
| U-07 | `PoliticaReputacion` | RN-07 | 4 | 55 | 30 |
| U-08 | `LimiteAportes` | RN-08 | 4 | 34 | 26 |
| U-09 | `SelectorPrecioVigente` | RN-09 | 5 | 43 | 38 |
| U-10 | `OrdenComparacion` | RN-10 | 4 | 59 | 50 |
| | **Total** | | **52** | **490** | **475** |

### 4.2 Detalle por suite

#### U-01 NormalizadorUnidad (RN-02: normalización de unidades)

**Archivo test:** `tests/Unit/Domains/Precios/Services/NormalizadorUnidadTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/NormalizadorUnidad.php`

| Caso de prueba | Entrada | Resultado esperado |
|---|---|---|
| Convierte gramos a kilogramos (0.001) | `500 g` | `0.5 kg` |
| Convierte mililitros a litros (0.001) | `330 mL` | `0.33 L` |
| Mantiene kg sin conversión (1) | `1 kg` | `1.0 kg` |
| Mantiene L sin conversión (1) | `2 L` | `2.0 L` |
| Mantiene unidad sin conversión (1) | `1 unidad` | `1.0 unidad` |
| Descompone multiempaque | `6 330 mL` | `1.98 L` |
| Cantidad cero lanza error | `0 kg` | `InvalidArgumentException` |
| Unidad desconocida lanza error | `1 oz` | `InvalidArgumentException` |

#### U-02 Dinero (RN-02: aritmética exacta sin floats)

**Archivo test:** `tests/Unit/Domains/Precios/Services/DineroTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/Dinero.php`

| Caso de prueba | Entrada | Resultado esperado |
|---|---|---|
| No usa float: $2.990 / 0.454 kg | `2990 COP / 0.454` | `$6.585,90` (exacto con BigDecimal) |
| Redondeo solo al mostrar | `Dinero::de('6585.903')` | `$6.585,90` (formato display) |
| Suma sin pérdida de precisión | `[1000.50, 2000.75, 500.25]` | `$3.501,50` |
| Precio por kg con gramos | `$2.990 / 500g` | `$5.980,00/kg` |

**Decisión técnica:** `Dinero::formatoDisplay()` extrae la parte entera y decimal por manipulación de strings, no por `toBigInteger()`, para evitar `RoundingNecessaryException` de brick/math.

#### U-03 Comparabilidad (RN-01: comparabilidad de unidades)

**Archivo test:** `tests/Unit/Domains/Precios/Services/ComparabilidadTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/Comparabilidad.php`

| Caso de prueba | Entrada | Resultado esperado |
|---|---|---|
| Misma unidad base → comparables | `kg, kg` | `true` |
| kg vs g → comparables (normalizables) | `kg, g` | `true` |
| mL vs L → comparables (normalizables) | `mL, L` | `true` |
| kg vs unidad → no comparables | `kg, unidad` | `false` |
| kg vs mL → no comparables (masa vs volumen) | `kg, mL` | `false` |
| Excepción estricta kg vs unidad | `kg, unidad` | `UnidadesIncompatiblesException` |

#### U-04 PoliticaVigencia (RN-04: vigencia por categoría)

**Archivo test:** `tests/Unit/Domains/Precios/Services/PoliticaVigenciaTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/PoliticaVigencia.php`

| Caso de prueba | Categoría | Antigüedad | Resultado |
|---|---|---|---|
| Perecedero 1 día | PERECEDERO | 1 día | `VIGENTE` |
| Perecedero 4 días | PERECEDERO | 4 días | `PUEDE_ESTAR_DESACTUALIZADO` |
| Perecedero 7 días | PERECEDERO | 7 días | `HISTORIAL` |
| Abarrote 5 días | ABARROTE | 5 días | `VIGENTE` |
| Abarrote 10 días | ABARROTE | 10 días | `PUEDE_ESTAR_DESACTUALIZADO` |
| Abarrote 15 días | ABARROTE | 15 días | `HISTORIAL` |

**Umbrales:** perecederos = 3 días, abarrotes = 7 días. Rango `PUEDE_ESTAR_DESACTUALIZADO` = entre 1 y 2 vigencias.

#### U-05 Corroboración (RN-05: corroboración cruzada)

**Archivo test:** `tests/Unit/Domains/Precios/Services/CorroboracionTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/Corroboracion.php`

| Caso de prueba | Resultado |
|---|---|
| 2 reportantes distintos, diferencia 5%, dentro de 48h | `CONFIABLE` |
| Mismo reportante dos veces | `NO_CORROBORADO` |
| Diferencia >5% entre aportes | `NO_CORROBORADO` |
| Aporte fuera de ventana de 48h | `NO_CORROBORADO` |
| Solo un aporte válido | `NO_CORROBORADO` |

**Decisión técnica:** `BigDecimal::dividedBy()` usa `RoundingMode::HALF_UP` para evitar excepción con decimales no terminantes (e.g., 50/3000).

#### U-06 DetectorAnomalias (RN-06: detección estadística)

**Archivo test:** `tests/Unit/Domains/Precios/Services/DetectorAnomaliasTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/DetectorAnomalias.php`

| Caso de prueba | Resultado |
|---|---|
| Precio dentro del rango MAD (n5) | `CONFIABLE` |
| Precio con \|M\| > 3.5 (n5) | `POR_VERIFICAR` |
| n<5 y precio dentro de 50% del historial | `CONFIABLE` |
| n<5 y precio fuera de 50% del historial | `POR_VERIFICAR` |
| MAD=0 (todos los precios iguales), precio igual | `CONFIABLE` |
| MAD=0, precio diferente | `POR_VERIFICAR` |

**Fórmula MAD:** `M = 0.6745  (precio - mediana) / MAD`, donde `MAD = mediana(|xi - mediana(x)|)`. Umbral: `|M| > 3.5`.

#### U-07 PoliticaReputacion (RN-07: reputación del contribuidor)

**Archivo test:** `tests/Unit/Domains/Precios/Services/PoliticaReputacionTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/PoliticaReputacion.php`

| Caso de prueba | Resultado |
|---|---|
| Cuenta nueva (<7 días) | `REQUIERE_CORROBORACION` |
| Cuenta con <5 aportes confirmados | `REQUIERE_CORROBORACION` |
| Cuenta con 7 días y 5 aportes | `PUEDE_CAMBIAR_VIGENTE` |
| Cuenta suspendida | `RECHAZADO` |

#### U-08 LimiteAportes (RN-08: rate limiting)

**Archivo test:** `tests/Unit/Domains/Precios/Services/LimiteAportesTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/LimiteAportes.php`

| Caso de prueba | Resultado |
|---|---|
| 30 aportes en la última hora | `PERMITIDO` |
| 31 aportes en la última hora | `LIMITE_EXCEDIDO` (HTTP 429) |
| Mismo producto+establecimiento+día | `REEMPLAZA` (no suma al conteo) |
| Producto+establecimiento distinto | `NUEVO_APORTE` |

#### U-09 SelectorPrecioVigente (RN-09: selector de visualización)

**Archivo test:** `tests/Unit/Domains/Precios/Services/SelectorPrecioVigenteTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/SelectorPrecioVigente.php`

| Confianza | Vigencia | Resultado |
|---|---|---|
| `CONFIABLE` | `VIGENTE` | `MOSTRAR` |
| `POR_VERIFICAR` | `VIGENTE` | `MOSTRAR_CON_ADVERTENCIA` |
| `NO_CONFIABLE` | `VIGENTE` | `NO_MOSTRAR` |
| `CONFIABLE` | `HISTORIAL` | `NO_MOSTRAR` |
| `CONFIABLE` | `PUEDE_ESTAR_DESACTUALIZADO` | `MOSTRAR_CON_ADVERTENCIA` |

#### U-10 OrdenComparacion (RN-10: orden neutral + AR-02)

**Archivo test:** `tests/Unit/Domains/Precios/Services/OrdenComparacionTest.php`
**Archivo impl.:** `app/Domains/Precios/Domain/Services/OrdenComparacion.php`

| Caso de prueba | Resultado |
|---|---|
| Orden por precio/unidad ascendente | El más barato primero |
| Empate de precio/unidad | El más reciente primero |
| Atributos comerciales no alteran el orden (AR-02) | Neutralidad verificada |
| Orden por distancia | El más cercano primero |

**Prueba AR-02 (neutralidad):** se crea un item con `esComercial: true` y se verifica que el orden no cambia respecto a los demás items. Los atributos comerciales se ignoran completamente en el algoritmo de comparación.

## 5. Análisis estático — PHPStan nivel 8

### 5.1 Configuración

```neon
# backend/phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    level: 8
    paths:
        - app
```

**Nivel 8** es el más estricto de PHPStan: verifica tipos de retorno, genéricos, uniones, tipos nulables y llamadas a métodos inexistentes.

### 5.2 Resultado

```
 36/36 [============================] 100%

 [OK] No errors
```

**0 errores en 36 archivos analizados.**

### 5.3 Errores encontrados y corregidos durante el ciclo

| Error | Archivo | Causa | Corrección |
|---|---|---|---|
| `Parameter $moneda of Money::of() expects Currency, Currency\|null given` | `Dinero.php:55` | `$moneda` podía ser null si el array estaba vacío | Se usó `$items[0]->money->getCurrency()` con anotación `@param non-empty-list<self>` |
| `array_values() redundante sobre list` | `OrdenComparacion.php:33,48` | PHPStan nivel 8 detecta que `usort` opera in-place sobre lista | Se eliminó `array_values()` en ambos métodos |
| `RoundingNecessaryException` en runtime | `Corroboracion.php:38` | `dividedBy()` sin modo de redondeo en decimales no terminantes | Se agregó `RoundingMode::HALF_UP` como tercer argumento |
| `RoundingNecessaryException` en runtime | `Dinero.php:formatoDisplay` | `toBigInteger()` falla con parte fraccionaria no cero | Se reemplazó por extracción de entero y decimal vía strings |

## 6. Resultados consolidados

### 6.1 Ejecución de pruebas

```
Tests:    77 passed (120 assertions)
Duration: 0.90s
```

| Tipo de prueba | Cantidad | Tiempo |
|---|---|---|
| Unitarias de dominio (U-01 a U-10) | 52 | < 0.2s |
| Unitarias de value objects | 14 | < 0.1s |
| Feature / integración (API + outbox) | 10 | ~ 0.6s |
| Ejemplo Laravel | 1 | < 0.01s |
| **Total** | **77** | **0.90s** |

### 6.2 Métricas de calidad cumplidas

| Métrica | Meta (plan-calidad) | Resultado | Estado |
|---|---|---|---|
| M-13 Cobertura del dominio (EC-10) | 80% líneas | 97,9 % de líneas del dominio (279/285), medido con pcov el 2026-11-16 (ver §11) | Cumple |
| M-14 Hallazgos críticos análisis estático | 0 abiertos | 0 errores PHPStan nivel 8 | Cumple |
| M-09 Neutralidad de comparación (AR-02) | 100% casos | 4/4 tests de OrdenComparacion pasan | Cumple |
| Suite de dominio < 60s (EC-10) | < 60s | 0.90s | Cumple |

## 7. Artefactos de soporte (Value Objects y Excepciones)

Además de los 10 servicios de dominio, se crearon los siguientes artefactos de soporte que modelan el lenguaje ubicuo del dominio:

| Tipo | Nombre | Propósito |
|---|---|---|
| Value Object | `CantidadNormalizada` | Cantidad + unidad base resultante de la normalización |
| Value Object | `CategoriaProducto` | Enum: `PERECEDERO`, `ABARROTE` |
| Value Object | `EstadoVigencia` | Enum: `VIGENTE`, `PUEDE_ESTAR_DESACTUALIZADO`, `HISTORIAL` |
| Value Object | `EstadoConfianza` | Enum: `CONFIABLE`, `NO_CORROBORADO`, `POR_VERIFICAR`, `NO_CONFIABLE` |
| Value Object | `AporteCorroboracion` | Datos de un aporte para el servicio de corroboración |
| Value Object | `CuentaContribuidora` | Datos de reputación de una cuenta |
| Value Object | `PermisoAporte` | Enum: `PUEDE_CAMBIAR_VIGENTE`, `REQUIERE_CORROBORACION`, `RECHAZADO` |
| Value Object | `ResultadoLimite` | Enum: `PERMITIDO`, `LIMITE_EXCEDIDO`, `REEMPLAZA`, `NUEVO_APORTE` |
| Value Object | `CandidatoPrecio` | Datos de entrada para el selector de precio vigente |
| Value Object | `ResultadoSelector` | Enum: `MOSTRAR`, `MOSTRAR_CON_ADVERTENCIA`, `NO_MOSTRAR` |
| Value Object | `ItemComparacion` | Datos de un item para el ordenamiento de comparación |
| Excepción | `UnidadesIncompatiblesException` | Lanzada por `Comparabilidad` al intentar comparar unidades de tipos distintos |

## 8. Trazabilidad con la matriz de requisitos

| Suite | Regla de negocio | Escenario de calidad | Métrica | Característica ISO 25010 |
|---|---|---|---|---|
| U-01 | RN-02 (normalización) | EC-06 (corrección) | M-09 | Adecuación funcional |
| U-02 | RN-02 (decimales) | EC-06 (corrección) | M-09 | Adecuación funcional |
| U-03 | RN-01 (comparabilidad) | EC-06 (corrección) | M-09 | Adecuación funcional |
| U-04 | RN-04 (vigencia) | EC-02 (actualidad) | M-02, M-15 | Eficiencia de desempeño |
| U-05 | RN-05 (corroboración) | EC-03 (credibilidad) | M-03 | Seguridad — integridad |
| U-06 | RN-06 (anomalías) | EC-03 (credibilidad) | M-03, M-04 | Seguridad — resistencia |
| U-07 | RN-07 (reputación) | EC-03 (credibilidad) | M-03 | Seguridad — autenticidad |
| U-08 | RN-08 (rate limit) | EC-03 (credibilidad) | M-03 | Seguridad — resistencia |
| U-09 | RN-09 (selector) | EC-03 (credibilidad) | M-15 | Adecuación funcional |
| U-10 | RN-10 (orden neutral) | EC-06 (neutralidad) | M-09 | Adecuación funcional |

## 9. Conclusiones

1. **Las 10 reglas de negocio están cubiertas por pruebas**: la cobertura medida de las líneas del dominio es 97,9 % (§11). En la versión del 2026-11-11 este punto decía «cobertura completa» sin un reporte que lo respaldara; se corrigió al medirla.

2. **El dominio es puro** (sin dependencias de framework): todos los servicios son clases `final` con métodos estáticos que reciben y devuelven Value Objects. Esto permite que los 52 tests corran en < 0.2 segundos.

3. **PHPStan nivel 8 con 0 errores** garantiza seguridad de tipos en todo el código de dominio. Los 4 errores encontrados durante el ciclo se documentan en la sección 5.3 como evidencia del proceso de refactorización.

4. **La aritmética monetaria usa BigDecimal** (brick/money), no `float`. Esto previene errores de redondeo acumulativo que afectaran la credibilidad de las comparaciones (ISO/IEC 25012 — exactitud).

5. **El ciclo rojo-verde-refactor está evidenciado** en el historial de Git y en los PRs #3, #4 y #5 con revisiones de código entre integrantes.

## 10. Declaración de uso de inteligencia artificial generativa

Conforme a la guía del proyecto (12.5), el equipo declara que utilizó el asistente de IA generativa **Claude (Anthropic)** como apoyo en las siguientes actividades de este entregable:

| Actividad | Uso de la IA | Revisión del equipo |
|---|---|---|
| Generación de esqueletos de prueba (rojo) | Sugerencia de estructura de tests Pest PHP para cada suite | El equipo validó que cada caso cubra las ramas de la regla de negocio |
| Implementación de servicios (verde) | Asistencia en la lógica de MAD, BigDecimal y manipulación de strings | El equipo revisó y corrigió errores de runtime (RoundingNecessaryException) |
| Corrección de PHPStan (refactor) | Diagnóstico de errores de tipos en nivel 8 | El equipo validó las correcciones |
| Redacción de este informe | Formato tabular y estructura del documento | El equipo validó que los datos sean exactos y correspondan al código real |

**Compromisos del equipo:** los umbrales de las reglas de negocio (vigencia 3/7 días, tolerancia 5%, ventana 48h, MAD 3.5, reputación 7 días/5 aportes, límite 30/h) fueron definidos por el equipo en la especificación de requisitos. Cada integrante está en capacidad de explicar y defender cualquier suite de pruebas y su implementación correspondiente ante el docente o los evaluadores.

Los commits que contienen código generado con asistencia de IA llevan la marca `Co-Authored-By: Claude` en el mensaje.

## 11. Actualización del 2026-11-16: cobertura medida y ciclos TDD con commits separados

### 11.1 Qué se corrigió del informe original

- La fila de cobertura citaba **M-10**, que es la medida de exposición de datos personales. La medida de
  cobertura del dominio es **M-13** (plan de aseguramiento de calidad §5).
- El informe afirmaba «100 % de ramas» sin un reporte de cobertura que lo respaldara. Ahora la cobertura se
  mide con **pcov** (`docker/php/Dockerfile`) y el CI la exige con `pest --coverage --min=80`
  (`.github/workflows/ci.yml`), publicando el reporte HTML y Clover como artefacto.
- §3.1 reconoce que en el corte 2 inicial pruebas e implementación entraron en el mismo commit. Eso no permite
  ver el ciclo en el historial. Los ciclos siguientes se hicieron con commits separados (§11.3).

### 11.2 Cobertura medida

Medición local del 2026-11-16 con PostgreSQL 16 + PostGIS y pcov, suite completa (153 pruebas):

| Alcance | Líneas cubiertas | Cobertura | Umbral M-13 |
|---|---|---|---|
| Dominio (`app/Domains/*/Domain`) | 279 / 285 | **97,9 %** | ≥ 80 % (objetivo 90 %) → cumple |
| Toda la aplicación (`app/`) | — | 96,5 % | — |
| Solo pruebas unitarias sobre toda la aplicación | — | 24,8 % | — (los adaptadores se cubren con pruebas Feature) |

Líneas del dominio sin cubrir: ramas defensivas en `Comparabilidad`, `Dinero`, `Corroboracion`,
`DetectorAnomalias` y `PoliticaReemplazoVigente` (p. ej., precio base cero). Quedan como tarea del corte 3.
La suite de dominio corre en menos de 1 s (EC-10: < 60 s).

### 11.3 Ciclos rojo → verde con commits separados (evidencia en el historial)

| Ciclo | Commit rojo (`test:`) | Commit verde (`feat:`) | Reglas / escenarios |
|---|---|---|---|
| Registro de reportes según el contrato | `b10c8d1` | `3e1b044` | RN-08, idempotencia (EC-09), C-07 |
| Confianza y comparación de extremo a extremo | `a2facb1` | `e260e3d` | RN-01, RN-02, RN-05, RN-06, RN-07, RN-09, RN-10, EC-02, EC-03, EC-06 |
| Cola offline de la PWA | `faa14b2` | `d9ffa21` | EC-09, M-12, RN-02 (formato) |

Antes de cada ciclo verde se ejecutó la suite para confirmar el fallo (por ejemplo, 20 pruebas de
`ComparacionTest` en rojo antes de `e260e3d`). El refactor estructural de contextos (`f44228e`, ADR-0016) se
hizo con la suite completa en verde antes y después.

### 11.4 Análisis estático al cierre

PHPStan nivel 8: 0 errores. Deptrac: 0 violaciones por contexto (AR-01, AR-02) y por capa. SonarQube no se
usó; PHPStan + Deptrac cubren M-14 para PHP (la justificación está en §2).

