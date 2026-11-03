# ADR-0004: API REST documentada con OpenAPI 3.1

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Responsables:** Carlos Augusto Aranzazu · Sofia Giraldo González · Miguel Angel Rodriguez
- **Escenarios relacionados:** EC-04, EC-07, EC-09; mínimo exigible de la guía (API documentada)
- **Estudio de respaldo:** [Análisis comparativo REST vs GraphQL](../estudios/estudio-rest-vs-graphql.md)

## Contexto

Los clientes son una PWA (consumidor/reportante) y una consola de moderación; en el corte 3 se suma el portal de comercios. Las consultas de comparación son la operación dominante del sistema y deben ser eficientes bajo carga (EC-04). Los datos de los reportantes —ubicación, actividad— son datos personales que no deben exponerse en respuestas públicas (EC-07). El registro de precio debe funcionar con conectividad móvil limitada y ser reintentable sin duplicar datos (EC-09).

La arquitectura adoptada incluye un modelo de lectura precalculado (`comparacion_vigente`, ADR-0003) que resuelve el problema de *underfetching* sin necesitar un lenguaje de consultas flexible en la capa de API.

## Alternativas consideradas

| Alternativa | A favor | En contra |
|---|---|---|
| **REST + OpenAPI 3.1** | Caché HTTP nativa (`GET` cacheable); contrato explícito y auditable para EC-07; herramientas maduras de prueba de contrato; sin dependencias adicionales más allá del framework | Posible sobre/infra-obtención de datos en APIs no optimizadas (mitigado por el modelo de lectura precalculado) |
| GraphQL | Consultas flexibles por cliente; *schema* aditivo facilita evolución | Caché HTTP más difícil (`POST` por defecto, requiere caché a nivel aplicación); superficie mayor para exponer datos personales por error (EC-07); problema N+1 requiere DataLoader; curva de aprendizaje para el equipo |
| gRPC | Eficiente en comunicación binaria | No nativo en navegador; no hay comunicación entre servicios internos que lo justifique |
| WebSockets | Tiempo real | La comparación no necesita *push*; evaluable para alertas opcionales en corte 3 |

## Decisión

Usaremos **REST con OpenAPI 3.1** como contrato de la API pública (`docs/03-arquitectura/api/openapi.yaml`), con los siguientes principios:

- Errores en formato *Problem Details* (RFC 9457).
- Autenticación *Bearer* (JWT).
- Versión en la ruta (`/v1`).
- Encabezado `Idempotency-Key` obligatorio en `POST /reportes-precio` (EC-09).
- Los esquemas de respuestas públicas **no incluyen** datos personales de reportantes (EC-07).
- `GET /comparaciones` siempre devuelve el campo `criterioOrden` explícito (EC-06, ADR-0009).

**Por qué no GraphQL:** la arquitectura CQRS con tabla `comparacion_vigente` (ADR-0003) ya resuelve el problema de *underfetching* que es la ventaja central de GraphQL en este dominio. La caché HTTP nativa de `GET` es determinante para EC-04. El contrato OpenAPI explícito es más fácil de auditar para EC-07. Ver el estudio comparativo completo en [estudios/estudio-rest-vs-graphql.md](../estudios/estudio-rest-vs-graphql.md).

## Consecuencias

- **Positivas:** caché en el borde para comparaciones (EC-04); prueba de contrato automática C-07 que verifica ausencia de campos personales (EC-07); documentación navegable generada desde el contrato; *pipeline* CI valida respuestas contra el *schema*.
- **Negativas / riesgos:** el contrato OpenAPI debe mantenerse sincronizado con la implementación — se controla con una puerta en CI que falla si el *schema* y las respuestas divergen.
- **Condiciones de revisión:** si el portal de comercios (corte 3) requiere un contrato significativamente distinto, se evaluará el patrón Backend for Frontend (BFF). Si se implementa el módulo de alertas opcional, se registrará el uso de WebSockets en un ADR independiente.
- **Verificación:** prueba de contrato C-07 (sin campos personales en respuestas públicas) y validación automática de respuestas contra el *schema* en CI.
