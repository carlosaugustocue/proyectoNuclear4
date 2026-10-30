# ADR-0001: Registrar las decisiones arquitectónicas con ADRs

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** todos (trazabilidad de decisiones)

## Contexto
La guía exige registrar cada decisión estructural con contexto, alternativas, decisión y consecuencias (§8.1, entregable 2 de AS II), y valora que una decisión inadecuada se identifique, documente y corrija.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| **ADRs en Markdown dentro del repositorio (Nygard, 2011)** | Versionados con el código; revisables en PR; historial | Requiere disciplina |
| Wiki o documento compartido | Fácil de editar | Se desincroniza del código; sin historial ligado a commits |

## Decisión
Usaremos ADRs en `docs/03-arquitectura/adr/`, numerados, con la plantilla `0000-plantilla.md`. Un ADR aceptado no se edita: se reemplaza con uno nuevo que lo referencia.

## Consecuencias
- **Positivas:** la evolución de la arquitectura (corte 2 y 3) queda trazada; facilita el análisis de EC-05.
- **Negativas:** costo de escritura; se mitiga con la plantilla corta.
- **Verificación:** la DoD exige ADR en todo PR con cambio estructural.
