# Proyecto Nuclear VI — Documentación

Plataforma colaborativa de comparación de precios entre tiendas y supermercados (Armenia, Quindío) · II-2026.

## Mapa de entregables del Corte 1

| Asignatura | Entregable de la guía | Archivo(s) |
|---|---|---|
| Arquitectura de Software II | **1. Documento de arquitectura v1** (contexto, requisitos y escenarios, ≥ 2 alternativas, C4, contrato API) | [03-arquitectura/documento-arquitectura-v1.md](03-arquitectura/documento-arquitectura-v1.md) · [api/openapi.yaml](03-arquitectura/api/openapi.yaml) |
| Arquitectura de Software II | **2. Registro inicial de ADRs** | [03-arquitectura/adr/](03-arquitectura/adr/) (0001–0013) |
| Modelos de Calidad | **1. Plan de aseguramiento de calidad** | [02-calidad/plan-aseguramiento-calidad.md](02-calidad/plan-aseguramiento-calidad.md) · [00-planteamiento/planteamiento-problema-y-abordaje-calidad.pdf](00-planteamiento/planteamiento-problema-y-abordaje-calidad.pdf) |
| Modelos de Calidad | **2. Especificación de requisitos y calidad de datos** (RF/RNF, escenarios refinados, 25012, matriz) | [01-requisitos/especificacion-requisitos.md](01-requisitos/especificacion-requisitos.md) · [01-requisitos/matriz-trazabilidad.md](01-requisitos/matriz-trazabilidad.md) |
| Fase 1 (insumo de ambas) | Entrevistas, encuesta, benchmarking | [01-requisitos/instrumentos-fase1.md](01-requisitos/instrumentos-fase1.md) |

## Mapa de entregables del Corte 2

| Asignatura | Entregable de la guía | Archivo(s) |
|---|---|---|
| Modelos de Calidad | **3. Informe TDD y análisis estático** (ciclo rojo-verde-refactor, PHPStan nivel 8, cobertura medida M-13) | [02-calidad/informe-tdd-analisis-estatico.md](02-calidad/informe-tdd-analisis-estatico.md) (§11: cobertura y commits `test:`→`feat:`) |
| Arquitectura de Software II | **3. Núcleo funcional desplegado** (registro y comparación de extremo a extremo, API documentada, despliegue reproducible) | `backend/` · `frontend/` · `docker-compose.yml` · `Makefile` · [`docker/railway/`](../docker/railway) · [api/openapi.yaml](03-arquitectura/api/openapi.yaml) 0.2.0 · guía en el [README](../README.md) |
| Modelos de Calidad | **4. Prototipo y prueba de usabilidad** | [02-calidad/protocolo-prueba-usabilidad.md](02-calidad/protocolo-prueba-usabilidad.md) · `frontend/` · evidencia primaria en [02-calidad/evidencias/](02-calidad/evidencias/) |
| Arquitectura de Software II | **4. Análisis de impacto del módulo Comercial y arquitectura v2** (ADRs nuevos) | [03-arquitectura/analisis-impacto-modulo-comercial.md](03-arquitectura/analisis-impacto-modulo-comercial.md) · [03-arquitectura/documento-arquitectura-v2.md](03-arquitectura/documento-arquitectura-v2.md) · ADR [0014](03-arquitectura/adr/0014-contexto-comercial-e-identidad-diferida.md), [0015](03-arquitectura/adr/0015-precio-oficial-frente-a-precio-reportado.md), [0016](03-arquitectura/adr/0016-nucleo-compartido-y-fronteras-por-contexto.md) |

## Mapa de entregables del Corte 3

| Asignatura | Entregable de la guía | Archivo(s) |
|---|---|---|
| Arquitectura de Software II | **5. Sistema integrado** (núcleo + Comercial + ampliación A1 y B2, desplegado, demostración) | `backend/` · `frontend/` · [`.github/workflows/cd.yml`](../.github/workflows/cd.yml) · [entrega continua](03-arquitectura/ampliacion-corte3/entrega-continua.md) · URLs en el [README](../README.md) |
| Modelos de Calidad | **5. Informe de evaluación de calidad** (ISO/IEC 25040 + 25012, integración, sistema y carga, incidencias) | [02-calidad/informe-evaluacion-calidad.md](02-calidad/informe-evaluacion-calidad.md) · [`pruebas-calidad/resultados/2026-11-25_1315/`](../pruebas-calidad/resultados/2026-11-25_1315/) · [auditoría de datos](02-calidad/evidencias/datos/) |
| Modelos de Calidad | **6. Calidad del proceso y plan de mejora** (CMMI V3.0 / ISO/IEC 33020 conceptual, efecto de B2, plan de producto y proceso) | [02-calidad/calidad-proceso-plan-mejora.md](02-calidad/calidad-proceso-plan-mejora.md) |
| Arquitectura de Software II | **6. Evaluación de la arquitectura** (ATAM reducido, riesgos, sensibilidad, trade-offs, estimado vs real de los dos cambios, decisiones que cambiaríamos) | [03-arquitectura/evaluacion-arquitectura.md](03-arquitectura/evaluacion-arquitectura.md) |
| Arquitectura de Software II | **Documento de arquitectura final y registro completo de ADRs** | [03-arquitectura/documento-arquitectura-final.md](03-arquitectura/documento-arquitectura-final.md) · [adr/](03-arquitectura/adr/) (0001–0026) |
| Ambas | **Documento resumen tipo artículo** (con subsección de la ampliación) | [documento-resumen.md](documento-resumen.md) |

## Convenciones

- **⚑** = valor propuesto por el equipo, pendiente de validar con evidencia de la Fase 1.
- Identificadores: `RF-xx` requisito funcional · `RNF-xx` no funcional · `RN-xx` regla de negocio · `EC-xx` escenario de calidad · `M-xx` medida · `R-xx` restricción · `ADR-xxxx` decisión.
- Los diagramas están en Mermaid (se ven en GitHub, en VS Code con la extensión de Mermaid o en https://mermaid.live). Para el documento en Word, exportarlos como imagen.

## Lista de verificación antes de entregar

**Decisiones de equipo pendientes**
- [x] ADR-0012: stack tecnológico — PHP 8.3 + Laravel 11 (API) + Vue 3 + Vite + Tailwind + PWA con Dexie (offline).
- [x] ADR-0013: Well-Architected Framework (AWS WAF) + proveedor de despliegue (Railway).
- [ ] Validar los pesos de la comparación de alternativas (arquitectura §4.1).
- [ ] Validar o ajustar los valores ⚑ (vigencia, corroboración, umbrales de EC-02 y EC-03).
- [x] Asignar responsables (plan de calidad §10).
- [ ] Revisar y aceptar (o justificar) ADR-0014 y ADR-0015.

**Trabajo de campo (Fase 1)**
- [ ] ≥ 3 entrevistas a consumidores, ≥ 3 a comerciantes (≥ 1 tienda de barrio), encuesta con ≥ 30 respuestas.
- [ ] Benchmarking verificado y citado en APA.
- [ ] Datos de variación de precios con fuente verificable (DANE/SIPSA) para el planteamiento.

**Forma**
- [ ] Commits con contenido de **todos** los integrantes y revisiones registradas en los PR (la guía verifica la contribución individual con el historial).
- [ ] Pasar los entregables a Word con formato APA (si el docente lo exige) y exportar los diagramas.
- [ ] Sección "Declaración de uso de IA generativa" en cada entregable (guía §12.5). Cada integrante debe poder explicar y defender todas las decisiones.
- [x] Actividades de ISO/IEC 25040:2024 verificadas contra el índice oficial (plan de calidad §8).
- [ ] Verificar los nombres en español de las subcaracterísticas de 25010:2023.

**Despliegue y evidencia (corte 2)**
- [ ] Desplegar en Railway y anotar las URL públicas en el README.
- [ ] Evidencia primaria de usabilidad y reporte axe en [02-calidad/evidencias/](02-calidad/evidencias/).
