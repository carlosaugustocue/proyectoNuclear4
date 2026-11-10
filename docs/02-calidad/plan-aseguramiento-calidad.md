# Plan de aseguramiento de la calidad — v1

> **Entregable 1 de Modelos de Calidad (Corte 1)** · Estado: borrador 30/10/2026.
> Complementa el documento "Planteamiento del problema y abordaje inicial de calidad" (pasos 1–6 de ISO/IEC 25010) y la [especificación de requisitos](../01-requisitos/especificacion-requisitos.md).

## 1. Propósito

Hacer de la calidad un objetivo **explícito y verificable desde el inicio**, no una revisión al final. Este plan define qué calidad importa para la plataforma, cómo se mide, qué umbral se acepta y qué actividades del equipo la aseguran.

## 2. Conceptos base adoptados

| Concepto | Significado en este proyecto |
|---|---|
| **Calidad del producto** | Grado en que la plataforma (software + datos) satisface las necesidades de consumidores, reportantes, comercios y moderadores. Se evalúa con ISO/IEC 25010:2023 (software) e ISO/IEC 25012 (datos). |
| **Calidad del proceso** | Grado en que la forma de trabajar del equipo (requisitos, diseño, código, pruebas, revisión, despliegue) produce de manera predecible un producto de calidad. Se reflexiona con referencia conceptual a CMMI v3.0 / ISO/IEC 330xx (corte 3). |
| **Gestión de la calidad** | Planificar, asegurar, controlar y mejorar la calidad: este plan es su instrumento. |
| **Aseguramiento de calidad (QA)** | Actividades **preventivas** sobre el proceso: definición de hecho, revisiones de pares, TDD, integración continua, ADRs. |
| **Control de calidad (QC)** | Actividades **de detección** sobre el producto: pruebas, análisis estático, pruebas de carga, pruebas con usuarios, auditoría de datos. |

**Relación producto–proceso.** Un proceso con TDD, revisión obligatoria y CI reduce defectos en las reglas de negocio (producto). Por eso cada característica de producto priorizada tiene al menos una actividad de proceso que la protege (sección 7).

## 3. Selección del modelo de calidad

### 3.1 Comparación con modelos clásicos

| Criterio | McCall (1977) | Boehm (1978) | FURPS+ (HP / Grady, 1987; "+" de Rational) | **ISO/IEC 25010:2023 + 25012** |
|---|---|---|---|---|
| Estructura | 3 perspectivas (operación, revisión, transición) → 11 factores → criterios → métricas | Jerárquica: utilidad general → utilidad tal cual / mantenibilidad / portabilidad → características primitivas | 5 categorías (funcionalidad, usabilidad, fiabilidad, desempeño, soportabilidad) + restricciones (diseño, implementación, interfaz, físicas) | 9 características, ~40 subcaracterísticas; división 2502n de medición; 25040 para evaluar |
| Fortaleza | Primer vínculo factor → métrica; distingue visión de usuario y de desarrollador | Detalla mantenibilidad y portabilidad; incluye ingeniería humana | Práctico para requisitos; las restricciones "+" capturan decisiones de diseño impuestas | Estándar internacional vigente, terminología común, alineado con medición y evaluación |
| Seguridad | Solo "integridad" | No explícita | Dentro de funcionalidad | Característica propia: confidencialidad, integridad, no repudio, responsabilidad, autenticidad, **resistencia** |
| Accesibilidad / inclusividad | No | Parcial (ingeniería humana) | Parcial (usabilidad) | **Inclusividad** dentro de capacidad de interacción |
| Escalabilidad | No | No | Parcial (desempeño) | **Escalabilidad** dentro de flexibilidad |
| Calidad de los datos | No | No | No | **Modelo dedicado (ISO/IEC 25012)** |
| Evaluación formal | Métricas propuestas sin proceso | Sin proceso | Sin proceso | ISO/IEC 25040 |
| Encaje con el proyecto | Bajo | Bajo–medio | Medio (útil para redactar requisitos) | **Alto** |

### 3.2 Decisión

Se adopta **ISO/IEC 25010:2023** para la calidad del producto de software, **ISO/IEC 25012** para la calidad de los datos e **ISO/IEC 25040** como marco de evaluación.

**Razones:**
1. El riesgo principal del producto es la **credibilidad y actualidad de datos colaborativos**; ningún modelo clásico modela la calidad de los datos.
2. Los escenarios EC-03 y EC-07 exigen subcaracterísticas de seguridad (resistencia, responsabilidad, confidencialidad) que McCall y Boehm no distinguen.
3. La guía exige WCAG 2.2 AA (inclusividad) y carga de 500 usuarios concurrentes (capacidad, escalabilidad), presentes solo en 25010:2023.
4. La familia SQuaRE integra modelo, medición y evaluación; los clásicos no tienen proceso de evaluación.

**Qué se conserva de los clásicos:** de McCall, la cadena factor → criterio → métrica (se refleja en característica → subcaracterística → medida); de FURPS+, la separación explícita de **restricciones** (legales, de equipo y de presupuesto), que se documentan en la especificación (R-01 a R-06).

## 4. Características priorizadas

Versión ajustada de la Tabla 2 del planteamiento (cambios marcados con ▲).

| Característica (25010:2023) | Prioridad | Subcaracterísticas críticas | Escenarios |
|---|---|---|---|
| Adecuación funcional | Alta | Corrección funcional, completitud funcional | EC-06, RN-01…RN-10 |
| Eficiencia de desempeño | Alta | Comportamiento temporal, capacidad | EC-04 |
| Capacidad de interacción | Alta | Operabilidad, protección contra errores del usuario, inclusividad | EC-01, EC-08 |
| Seguridad | Alta | Integridad, **resistencia ▲**, autenticidad, responsabilidad, confidencialidad | EC-03, EC-07 |
| Mantenibilidad | Alta | Modularidad, modificabilidad, capacidad de ser probado | EC-05, EC-10 |
| Fiabilidad | **Media → con escenario ▲** | Tolerancia a fallos, disponibilidad | EC-09 |
| Flexibilidad | Media | Escalabilidad | EC-04 |
| Compatibilidad | **Media ▲** | Interoperabilidad (SIPSA, futuro portal de comercios) | RF-25 |
| Protección (safety) | Baja | — | No aplica |

▲ Compatibilidad sube a media porque la guía propone el SIPSA como fuente inicial y el módulo comercial integra información de otra fuente.

Las características de **datos** (ISO/IEC 25012) priorizadas y sus medidas están en la sección 9 de la especificación de requisitos.

## 5. Medidas y umbrales

Cada medida sigue la forma de la división ISO/IEC 2502n: `X = A / B` con su función de medición. Umbral en tres niveles: **no aceptable / aceptable / objetivo**.

| Medida | Función | No aceptable | Aceptable | Objetivo | Instrumento |
|---|---|---|---|---|---|
| M-01 Registros ≤ 15 s | X = registros ≤ 15 s / intentos | < 0,80 | ≥ 0,80 | ≥ 0,90 | Prueba con usuarios, cronómetro en la app |
| M-02 Latencia de actualidad | T = t(retiro) − t(cambio) | > 5 min | ≤ 5 min | ≤ 1 min | Prueba de integración |
| M-03 Exhaustividad de detección | X = anómalos detectados / anómalos reales | < 0,80 | ≥ 0,80 | ≥ 0,90 | Conjunto etiquetado |
| M-04 Falsos positivos | X = legítimos marcados / legítimos | > 0,05 | ≤ 0,05 | ≤ 0,03 | Conjunto etiquetado |
| M-05 p95 consulta | percentil 95 del tiempo de respuesta | > 2 s | ≤ 2 s | ≤ 1 s | k6 |
| M-06 Tasa de error bajo carga | X = respuestas 5xx / total | ≥ 0,01 | < 0,01 | < 0,001 | k6 |
| M-07 Cambios estructurales al núcleo | cuenta | ≥ 1 | 0 | 0 | Prueba de arquitectura + revisión |
| M-08 Precisión de la estimación | X = esfuerzo real / estimado | > 1,3 | ≤ 1,3 | ≤ 1,15 | Registro de horas por tarea |
| M-09 Neutralidad | X = casos neutrales / casos | < 1 | 1 | 1 | Pruebas U-10, C-06, AR-02 |
| M-10 Exposición de datos personales | campos personales en respuestas públicas | ≥ 1 | 0 | 0 | Prueba de contrato |
| M-11 Accesibilidad | violaciones A/AA (axe) en flujos críticos | ≥ 1 | 0 | 0 | axe + revisión manual |
| M-12 Sincronización offline | X = sincronizados sin duplicar / registrados offline | < 1 | 1 | 1 | Prueba E2E |
| M-13 Cobertura del dominio | líneas cubiertas / líneas del dominio | < 0,80 | ≥ 0,80 | ≥ 0,90 | Reporte de cobertura |
| M-14 Hallazgos críticos de análisis estático | cuenta abierta al cierre del corte | ≥ 1 | 0 | 0 | SonarQube / SonarCloud |
| M-15 Precios vencidos mostrados como vigentes | X = vencidos sin advertencia / mostrados | > 0 | 0 | 0 | Auditoría de datos (consulta SQL) |

**Justificación de umbrales propios (⚑):**
- **M-02 (5 min):** un consumidor decide la compra en el momento; los precios en tiendas cambian, como mucho, a diario. Cinco minutos son tres órdenes de magnitud menores que la frecuencia de cambio y permiten una proyección asíncrona simple (ADR-0003). Se valida con lo que digan los comerciantes en las entrevistas.
- **M-03/M-04 (80 % / 5 %):** priorizar exhaustividad sin falsos positivos perjudicaría a tiendas con precios bajos legítimos (equidad, guía §12). Un falso positivo no oculta el precio: lo muestra con advertencia y lo envía a moderación, por eso se tolera 5 %.

## 6. Estrategia de pruebas

```
            ▲  Pruebas con usuarios (EC-01) · Accesibilidad manual (EC-08)
           ▲▲  Carga k6 (EC-04)
          ▲▲▲  Sistema / E2E: registro, offline, comparación (EC-06, EC-09)
         ▲▲▲▲  Contrato API contra OpenAPI (EC-07) · Arquitectura (EC-05, EC-06)
        ▲▲▲▲▲  Integración con BD real en contenedor (EC-02, EC-03)
       ▲▲▲▲▲▲  Unitarias del dominio con TDD (RN-01…RN-10, EC-10)
```

| Nivel | Qué cubre | Cuándo corre | Herramienta (según stack, ADR-0012) |
|---|---|---|---|
| Unitarias (TDD) | Reglas RN-01…RN-10; dominio puro, sin BD | Cada commit (CI) | Framework xUnit del stack |
| Integración | Repositorios, proyección del modelo de lectura, detector sobre datos reales | Cada PR | Testcontainers + PostgreSQL/PostGIS |
| Contrato | Respuestas conformes a `openapi.yaml`; sin campos personales | Cada PR | Validador OpenAPI / Schemathesis o similar |
| Arquitectura | Dependencias entre módulos permitidas (Comparación no depende de Comercial) | Cada PR | ArchUnit / dependency-cruiser / import-linter según stack |
| Sistema / E2E | Flujos completos en navegador, modo offline | Antes de cada entrega | Playwright |
| Carga | EC-04 con 500 usuarios virtuales | Antes de cada entrega (corte 2 y 3) | k6 |
| Usuarios | EC-01 con ≥ 8 participantes reales | Corte 2 | Protocolo de prueba + cronómetro |
| Accesibilidad | WCAG 2.2 AA | Corte 2 y 3 | axe + lector de pantalla (TalkBack/NVDA) |
| Análisis estático | Defectos, *code smells*, vulnerabilidades | Cada PR | SonarCloud (gratuito para repos públicos) |

**Ciclo TDD acordado (RDA3):** rojo (prueba que falla, commit `test:`) → verde (mínimo código, commit `feat:`) → refactor (commit `refactor:`). El historial de commits con esa secuencia es la **evidencia** que pide el entregable 3 del corte 2.

## 7. Aseguramiento de la calidad del proceso

| Práctica | Qué protege | Evidencia |
|---|---|---|
| Definición de "hecho" (DoD): pruebas pasan, cobertura del dominio ≥ 80 %, revisión aprobada, OpenAPI actualizado, ADR si hubo decisión estructural | Todas | Plantilla de PR |
| Revisión de pares obligatoria en cada PR (al menos 1 integrante distinto del autor) | Corrección, mantenibilidad | Historial de PRs en Git |
| Integración continua con puertas de calidad (pruebas, cobertura, análisis estático, prueba de arquitectura) | EC-05, EC-10 | Pipeline en verde |
| ADRs para cada decisión estructural | Mantenibilidad, trazabilidad | `docs/03-arquitectura/adr` |
| Commits convencionales y ramas cortas | Trazabilidad del trabajo individual (guía §10) | Historial Git |
| Registro de esfuerzo estimado vs real por tarea | EC-05 (M-08) | Tablero del equipo |
| Retrospectiva al cierre de cada corte | Mejora del proceso (entregable 6) | Acta breve |
| Declaración del uso de IA generativa | Integridad académica (guía §12.5) | Sección en cada entregable |

**Medidas de proceso** (para el entregable 6, corte 3): % de PRs revisados por otro integrante (meta 100 %), defectos escapados a la entrega, tiempo medio de PR abierto → fusionado, precisión de estimación (M-08).

## 8. Evaluación según ISO/IEC 25040

La evaluación final (corte 3) seguirá las actividades del proceso de evaluación de ISO/IEC 25040. *Pendiente: verificar la denominación exacta de las actividades en la edición 2024; en la edición 2011 son:*

1. **Establecer los requisitos de la evaluación:** propósito, partes del producto y características (secciones 3–4).
2. **Especificar la evaluación:** medidas y criterios de decisión (sección 5).
3. **Diseñar la evaluación:** plan de actividades, datos de prueba, herramientas y responsables (sección 6 y cronograma).
4. **Ejecutar la evaluación:** correr pruebas y registrar resultados contra la matriz de trazabilidad.
5. **Concluir la evaluación:** comparar con umbrales, reportar incidencias y su tratamiento.

## 9. Riesgos de calidad

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| No conseguir ≥ 8 usuarios para EC-01 | Media | Alto | Reclutar compañeros de otros semestres y familiares desde el corte 1 |
| Datos de prueba insuficientes para EC-03 | Alta | Alto | Generar conjunto sintético etiquetado + datos reales de campo |
| El entorno gratuito de nube no soporta 500 VU | Media | Medio | Ejecutar la carga contra el despliegue en contenedores en máquina local o VM académica; documentar el entorno |
| Umbrales propios sin evidencia | Media | Medio | Ajustarlos con entrevistas a comerciantes (Fase 1) |
| Carga desigual en el equipo | Media | Alto | Asignación explícita por módulo y revisión cruzada |

## 10. Responsables (por completar)

| Rol | Integrante |
|---|---|
| Responsable de calidad (plan, matriz, métricas) | Sofía Giraldo González |
| Responsable de arquitectura (C4, ADRs) | Miguel Ángel Rodríguez |
| Responsable de pruebas y CI | Carlos Augusto Aranzazu Jinete |

## 11. Declaración de uso de inteligencia artificial generativa

Conforme a la guía del proyecto (§12.5), el equipo declara que utilizó el asistente de IA generativa **Claude (Anthropic)** como apoyo en las siguientes actividades de este documento:

| Actividad | Uso de la IA | Revisión del equipo |
|---|---|---|
| Comparación de modelos de calidad (§3.1) | Formato tabular y redacción de criterios comparativos | Validación de criterios y ajuste de argumentos por el equipo |
| Definición de medidas (§5) | Sugerencia de formato ISO/IEC 2502n (X = A/B); los umbrales son del equipo | Verificación de coherencia con los escenarios y ajuste de valores ⚑ |
| Pirámide de pruebas (§6) | Estructura visual en texto; los niveles y herramientas son del equipo | Revisión de completitud contra la matriz de trazabilidad |
| Redacción de riesgos de calidad (§9) | Borradores de descripción; probabilidad e impacto son del equipo | Revisión de pertinencia |

**Compromisos del equipo:** las características priorizadas, los umbrales de aceptación y la estrategia de pruebas fueron decididos por los integrantes a partir del análisis del problema. Cada integrante está en capacidad de explicar y defender cualquier sección de este documento ante el docente o los evaluadores.
