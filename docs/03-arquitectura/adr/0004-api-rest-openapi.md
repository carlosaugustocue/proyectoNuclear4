# ADR-0004: API REST documentada con OpenAPI 3.1

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-07, EC-09, EC-04; mínimo exigible de la guía (API documentada)

## Contexto
Los clientes son una PWA (consumidor/reportante) y una consola de moderación; en el corte 3 se suma el portal de comercios. Las consultas son cacheables y los registros deben ser reintentables sin duplicar (EC-09).

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| **REST + OpenAPI 3.1** | Cache HTTP; herramientas maduras de contrato y pruebas; fácil de consumir | Posible sobre/infra-obtención de datos |
| GraphQL | Consultas flexibles por cliente | Cache HTTP más difícil; superficie mayor para exponer datos personales por error (EC-07); curva de aprendizaje |
| gRPC | Eficiente | No nativo en navegador; no hay comunicación entre servicios que lo justifique |
| WebSockets | Tiempo real | La comparación no necesita *push*; quizá para alertas (opcional, corte 3) |

## Decisión
Usaremos **REST con OpenAPI 3.1** como contrato (`docs/03-arquitectura/api/openapi.yaml`), errores *Problem Details* (RFC 9457), autenticación *Bearer* (JWT), versión en la ruta (`/v1`), y encabezado `Idempotency-Key` obligatorio en `POST /reportes-precio`. Los esquemas públicos no incluyen datos personales de reportantes.

## Consecuencias
- **Positivas:** pruebas de contrato automáticas (C-xx), cache en el borde para comparaciones, documentación navegable.
- **Negativas:** el contrato debe mantenerse sincronizado (puerta en CI).
- **Verificación:** prueba de contrato C-07 (sin campos personales) y validación de respuestas contra el esquema.
