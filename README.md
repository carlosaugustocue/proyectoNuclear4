# Plataforma colaborativa de comparación de precios — Proyecto Nuclear VI

Diseño, evolución y evaluación de la calidad de una plataforma colaborativa de comparación de precios entre tiendas de barrio, minimercados y supermercados de **Armenia y el Quindío**.

Proyecto del Seminario Nuclear 4 · Ingeniería de Software · Corporación Universitaria Empresarial Alexander von Humboldt · Periodo II-2026.

| Asignatura | Docente |
|---|---|
| Arquitectura de Software II | Rubén Darío Díaz Bautista |
| Modelos de Calidad | Rubén Darío Díaz Bautista |

## El problema

Un mismo producto puede tener precios muy distintos según el establecimiento, pero el consumidor rara vez tiene información para comparar antes de comprar. La plataforma permite que los usuarios registren los precios que observan y que cualquier persona los compare. El reto técnico es presentar como **confiable, actualizada y neutral** información que proviene de fuentes con distinta confiabilidad e intereses.

**Alcance:** plataforma informativa. Fuera del alcance: venta en línea, pagos, pedidos y domicilios.

## Estado

| Corte | Entregables | Estado |
|---|---|---|
| 1 (30 %) | Arquitectura v1 + ADRs · Plan de calidad + especificación de requisitos | 🟡 En revisión del equipo |
| 2 (30 %) | Núcleo desplegado · Análisis de impacto y arquitectura v2 · Informe TDD · Prueba de usabilidad | ⚪ Pendiente |
| 3 (40 %) | Sistema integrado · Evaluación de arquitectura · Evaluación de calidad · Documento resumen | ⚪ Pendiente |

## Estructura del repositorio

```
.
├── docs/
│   ├── README.md                         Índice y lista de verificación de entregas
│   ├── 00-planteamiento/                 Planteamiento del problema y abordaje inicial de calidad (PDF)
│   ├── 01-requisitos/                    Especificación, matriz de trazabilidad, instrumentos de la Fase 1
│   ├── 02-calidad/                       Plan de aseguramiento de la calidad
│   └── 03-arquitectura/
│       ├── documento-arquitectura-v1.md  Alternativas, modelo C4, riesgos y trade-offs
│       ├── adr/                          Registro de decisiones arquitectónicas
│       └── api/openapi.yaml              Contrato de la API (OpenAPI 3.1)
├── .github/                              Plantilla de pull request
└── CONTRIBUTING.md                       Flujo de trabajo del equipo
```

El código fuente se agregará en el corte 2 (ver [ADR-0012](docs/03-arquitectura/adr/0012-stack-tecnologico.md)).

## Decisiones principales

| Decisión | Motivación | ADR |
|---|---|---|
| Monolito modular organizado por contextos de dominio | Modificabilidad (EC-05) con el costo operacional que puede asumir un equipo de 3 | [0002](docs/03-arquitectura/adr/0002-monolito-modular.md) |
| CQRS ligero: modelo de lectura proyectado mediante *outbox* | Consultas rápidas (EC-04) con actualidad acotada (EC-02) | [0003](docs/03-arquitectura/adr/0003-cqrs-ligero-outbox.md) |
| PWA con registro *offline-first* | Registro en ≤ 15 s con conectividad limitada (EC-01, EC-09) | [0006](docs/03-arquitectura/adr/0006-pwa-offline-first.md) |
| Modelo de confianza explicable: marcar, no borrar | Credibilidad de los datos sin perjudicar a comercios legítimos (EC-03) | [0007](docs/03-arquitectura/adr/0007-modelo-confianza.md) |
| Neutralidad garantizada por diseño | El orden de la comparación no depende de relaciones comerciales (EC-06) | [0009](docs/03-arquitectura/adr/0009-neutralidad-comparacion.md) |

Modelo de calidad: **ISO/IEC 25010:2023** (producto), **ISO/IEC 25012** (datos) e **ISO/IEC 25040** (evaluación). La justificación frente a McCall, Boehm y FURPS+ está en el [plan de calidad](docs/02-calidad/plan-aseguramiento-calidad.md).

## Cómo leer la documentación

Los diagramas (C4, secuencia, mapa de contextos) están escritos en [Mermaid](https://mermaid.js.org/) y GitHub los muestra directamente. El contrato de la API se puede visualizar pegando `docs/03-arquitectura/api/openapi.yaml` en [Swagger Editor](https://editor.swagger.io/).

## Equipo

| Integrante | GitHub |
|---|---|
| Carlos Augusto Aranzazu Jinete | [@carlosaugustocue](https://github.com/carlosaugustocue) |
| Miguel Ángel Rodríguez | — |
| Sofía Giraldo González | — |

> Cada integrante completa su usuario de GitHub en su propio *pull request* (ver [CONTRIBUTING.md](CONTRIBUTING.md)).

## Uso de inteligencia artificial generativa

Conforme a la guía del proyecto (§12.5), el equipo declara el uso de asistentes de IA generativa (Claude) como apoyo en la redacción y estructuración de la documentación. Los commits asistidos lo indican con un *trailer* `Co-Authored-By`. Todas las decisiones fueron revisadas por el equipo, que responde por ellas.
