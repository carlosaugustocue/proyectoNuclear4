# Proyecto Nuclear VI — Documentación del Corte 1

Plataforma colaborativa de comparación de precios entre tiendas y supermercados (Armenia, Quindío) · II-2026.

## Mapa de entregables del Corte 1

| Asignatura | Entregable de la guía | Archivo(s) |
|---|---|---|
| Arquitectura de Software II | **1. Documento de arquitectura v1** (contexto, requisitos y escenarios, ≥ 2 alternativas, C4, contrato API) | [03-arquitectura/documento-arquitectura-v1.md](03-arquitectura/documento-arquitectura-v1.md) · [api/openapi.yaml](03-arquitectura/api/openapi.yaml) |
| Arquitectura de Software II | **2. Registro inicial de ADRs** | [03-arquitectura/adr/](03-arquitectura/adr/) (0001–0012) |
| Modelos de Calidad | **1. Plan de aseguramiento de calidad** | [02-calidad/plan-aseguramiento-calidad.md](02-calidad/plan-aseguramiento-calidad.md) · [00-planteamiento/planteamiento-problema-y-abordaje-calidad.pdf](00-planteamiento/planteamiento-problema-y-abordaje-calidad.pdf) |
| Modelos de Calidad | **2. Especificación de requisitos y calidad de datos** (RF/RNF, escenarios refinados, 25012, matriz) | [01-requisitos/especificacion-requisitos.md](01-requisitos/especificacion-requisitos.md) · [01-requisitos/matriz-trazabilidad.md](01-requisitos/matriz-trazabilidad.md) |
| Fase 1 (insumo de ambas) | Entrevistas, encuesta, benchmarking | [01-requisitos/instrumentos-fase1.md](01-requisitos/instrumentos-fase1.md) |

## Convenciones

- **⚑** = valor propuesto por el equipo, pendiente de validar con evidencia de la Fase 1.
- Identificadores: `RF-xx` requisito funcional · `RNF-xx` no funcional · `RN-xx` regla de negocio · `EC-xx` escenario de calidad · `M-xx` medida · `R-xx` restricción · `ADR-xxxx` decisión.
- Los diagramas están en Mermaid (se ven en GitHub, en VS Code con la extensión de Mermaid o en https://mermaid.live). Para el documento en Word, exportarlos como imagen.

## Lista de verificación antes de entregar

**Decisiones de equipo pendientes**
- [x] ADR-0012: stack tecnológico — PHP 8.3 + Laravel 11 (API) + Vue 3 + Vite + Workbox (PWA).
- [x] ADR-0013: Well-Architected Framework (AWS WAF) + proveedor de despliegue (Railway).
- [ ] Validar los pesos de la comparación de alternativas (arquitectura §4.1).
- [ ] Validar o ajustar los valores ⚑ (vigencia, corroboración, umbrales de EC-02 y EC-03).
- [ ] Asignar responsables (plan de calidad §10) y el contexto o módulo de cada integrante.

**Trabajo de campo (Fase 1)**
- [ ] ≥ 3 entrevistas a consumidores, ≥ 3 a comerciantes (≥ 1 tienda de barrio), encuesta con ≥ 30 respuestas.
- [ ] Benchmarking verificado y citado en APA.
- [ ] Datos de variación de precios con fuente verificable (DANE/SIPSA) para el planteamiento.

**Forma**
- [ ] Repositorio Git creado con esta carpeta `docs/` y commits de **todos** los integrantes (la guía verifica la contribución individual con el historial).
- [ ] Pasar los entregables a Word con formato APA (si el docente lo exige) y exportar los diagramas.
- [ ] Sección "Declaración de uso de IA generativa" en cada entregable (guía §12.5). Cada integrante debe poder explicar y defender todas las decisiones.
- [ ] Verificar contra las normas: nombres en español de las subcaracterísticas de 25010:2023 y actividades de 25040:2024.
