# Matriz de trazabilidad — Núcleo v1

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

> **Nota:** la matriz se actualiza en cada corte. En el corte 2 se agregan las filas del módulo comercial (RF-C01…) y la columna "resultado" con la evidencia de cada prueba.
