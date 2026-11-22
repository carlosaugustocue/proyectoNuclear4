# ADR-0026: Aportes con cuenta: confirmar «sigue igual», marcar como dudoso y mis aportes

- **Estado:** Propuesto · **Fecha:** 2026-11-22
- **Responsable(s):** equipo (pruebas y CI: Carlos Augusto Aranzazu Jinete)
- **Escenarios relacionados:** EC-01, EC-03, EC-04, EC-07 · **Requisitos:** RF-10, RF-12, RF-19, RN-05, RN-07, RN-08
- **Relacionado con:** ADR-0003 (CQRS y outbox), ADR-0007 (confianza: «marcar, no borrar»), ADR-0025 (cuentas)

## Contexto
Con las cuentas de ADR-0025 quedaron desbloqueados tres requisitos que el contrato tenía como planificados:
- **RF-10:** confirmar con un toque que un precio mostrado «sigue igual».
- **RF-19:** marcar un precio como dudoso, con un motivo.
- **RF-12:** consultar los aportes propios y su estado.

Había que decidir qué es una confirmación para el modelo de confianza, cómo evitar que las marcas inunden la cola de moderación y qué informar en «mis aportes» sin exponer datos de otros (EC-07).

## Alternativas consideradas
| Tema | Alternativa | A favor | En contra |
|---|---|---|---|
| Confirmación | A. Contador `confirmaciones++` sobre la observación | Simple | Salta RN-05 a RN-08: no hay ventana de 48 h, ni límite de aportes, ni reputación; un contador no dice *quién* confirmó, así que una cuenta podría confirmar mil veces |
| | **B. Registrar una observación nueva** del mismo precio, a nombre de quien confirma y con fecha actual | Reutiliza todo el flujo (idempotencia, RN-08, outbox, Confianza, proyección); la corroboración RN-05 sale sola; queda trazado | Una fila más por confirmación |
| Qué precio se confirma | A. La observación más reciente del establecimiento | Una consulta a Precios | **Falla de verdad**: RN-07 puede dejar publicado un precio más viejo que el último reporte (lo detectó la prueba en navegador) |
| | **B. La observación que la comparación muestra** | Coincide con lo que el usuario ve | Confianza necesita leer el modelo de Comparación |
| Dependencia para B | A. Confianza importa Comparación | Directo | Rompe AR-01 (Comparación ya depende de Confianza) |
| | **B. Puerto `PrecioPublicado` en Confianza, implementado por Comparación** | Respeta AR-01; Deptrac lo verifica | Una interfaz más |
| Marcas | A. Un caso de moderación por marca | Simple | Diez usuarios marcan lo mismo y el moderador ve diez casos |
| | **B. Una marca por cuenta y precio; todas se suman a un caso MARCA_USUARIO** | Una sola decisión por precio; no se puede marcar dos veces | Hace falta una tabla de marcas |
| «Veces consultado» (RF-12) | A. Contar cada lectura de la comparación | Cumple la letra de RF-12 | Convierte la lectura más caliente (EC-04, p95 ≤ 2 s) en escritura y rompe el modelo de lectura de ADR-0003 |
| | **B. No medirlo; mostrar la reputación RN-07** | Útil para el reportante: sabe cuánto falta para que sus aportes cuenten solos | Se aparta de la redacción de RF-12 (prioridad C) |

## Decisión
- **RF-10:** `POST /v1/observaciones/{id}/confirmaciones` (Confianza, `ConfirmarPrecio`).
  - Llama a `RegistrarReportePrecioHandler` con el mismo monto, la fecha actual y la cuenta como reportante.
  - Responde `409` si el precio es propio, ya no es el publicado o fue invalidado (`NO_CONFIABLE`).
  - Es idempotente por `Idempotency-Key`: un reintento devuelve `200` con la misma observación. La PWA conserva la clave si la red falla (EC-09).
- **Precio publicado:** puerto `Confianza\Domain\Ports\PrecioPublicado`, implementado por `Comparacion\Infrastructure\PrecioPublicadoComparacion` sobre `comparacion_vigente`.
- **RF-19:** `POST /v1/observaciones/{id}/marcas` (Moderación, `MarcarObservacion`).
  - Tabla `marcas_observacion` con única `(observacion_id, cuenta_id)`; se borra en cascada al suprimir la cuenta (RF-23), porque el comentario es texto libre.
  - Abre o amplía un caso `MARCA_USUARIO` cuyo detalle lleva el motivo y el comentario, nunca quién marcó.
  - La marca no cambia la comparación (ADR-0007).
- **RF-12:** `GET /v1/mis-aportes` con producto, presentación, establecimiento, precio, fecha y estado, más `reputacion` (`ConsultarReputacion`, que comparte el cálculo con la evaluación de confianza). Respuesta `no-store`.
- **Comparación:** cada ítem expone `observacionId`, un UUID opaco. La prueba C-07 verifica que no enlaza con el reportante.

## Consecuencias
- **Positivas:**
  - Confirmar corrobora de verdad (RN-05) y respeta el límite RN-08 y la reputación RN-07, sin código de confianza nuevo.
  - Un precio marcado por muchos genera una sola decisión de moderación.
  - El reportante ve el estado de sus aportes y su avance de reputación.
- **Negativas:**
  - Se expone el id de la observación. Mitigado: es opaco y ningún endpoint público lo asocia a una persona.
  - RF-12 se cumple sin el conteo de consultas; queda registrado aquí y en la especificación.
- **Seguimiento:**
  - E2E S-10 (confirmar en Playwright) dentro del lote de pruebas de sistema.
  - Pasar a Aceptado cuando el equipo lo revise.
