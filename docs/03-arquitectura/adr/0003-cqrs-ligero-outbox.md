# ADR-0003: CQRS ligero — modelo de lectura de comparación proyectado mediante outbox transaccional

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-04, EC-02, EC-01, pregunta de diseño 8

## Contexto
Las consultas superan ampliamente a los registros. Calcular en cada consulta el precio vigente, su confianza y su normalización exigiría *joins* y agregaciones costosas. Al mismo tiempo, la comparación debe reflejar cambios en un plazo acotado (EC-02) y el registro debe ser rápido (EC-01), sin esperar validaciones pesadas.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Consultar el modelo transaccional directamente | Siempre actual; simple | Consultas costosas bajo carga; mezcla lógica de confianza en la ruta de lectura |
| Vista materializada refrescada periódicamente | Muy simple | Refresco total costoso al crecer; latencia fija |
| **Modelo de lectura proyectado por eventos (outbox + worker)** | Lectura por índice; latencia de segundos; desacopla módulos | Consistencia eventual; un proceso más |
| Event Sourcing completo | Historial total, reproyección | Complejidad sin escenario que la exija |
| Broker externo (RabbitMQ/Kafka) | Estándar en sistemas distribuidos | Infraestructura adicional innecesaria en un monolito |

## Decisión
Cada caso de uso de escritura guarda su cambio y el evento de dominio en la tabla `outbox` **en la misma transacción**. Un *worker* lee la tabla (sondeo cada 2–5 s, ⚑) y entrega los eventos a los manejadores de Confianza y Comparación, que actualizan la tabla desnormalizada `comparacion_vigente`. Los manejadores son idempotentes. Se responde de inmediato al registro (202) y se difieren la evaluación de confianza y la proyección.

## Consecuencias
- **Positivas:** consultas rápidas (EC-04); latencia de actualización acotada (EC-02); el módulo comercial podrá publicar eventos al mismo *outbox* (EC-05).
- **Negativas:** consistencia eventual visible (un reporte tarda segundos en aparecer); hay que monitorear la antigüedad del *outbox*.
- **Verificación:** prueba I-02 (M-02 ≤ 5 min); métrica "edad del evento más antiguo sin procesar".
