# ADR-0018: La canasta se guarda en el dispositivo y se envía completa en cada consulta (sin persistencia en el servidor)

- **Estado:** Aceptado · **Fecha:** 2026-11-21
- **Responsable(s):** equipo (arquitectura: Miguel Ángel Rodríguez)
- **Escenarios relacionados:** EC-07, EC-11, EC-09

## Contexto
El mínimo exigible de A1 dice «crear una canasta de hasta 20 productos, **con o sin cuenta**». El contexto Identidad está diferido (ADR-0014): no hay cuentas de consumidor. La canasta revela hábitos de compra, y la guía (§12) exige privacidad por diseño y la Ley 1581 de 2012.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Canasta como recurso del servidor (`POST /canastas` → id; `GET /canastas/{id}/comparacion`) | Compartible por enlace; base para multidispositivo | Persiste hábitos de compra asociables a un dispositivo. Requiere una política de retención y un nuevo dato personal (EC-07). Más endpoints y más estado. |
| Canasta asociada a una cuenta | Multidispositivo | No existe Identidad (ADR-0014); incumple el «sin cuenta» |
| **Canasta en el dispositivo y consulta sin estado** (`POST /v1/canastas/comparacion` con la lista completa) | Cumple «sin cuenta». El servidor no guarda nada (0 datos personales nuevos). Sin estado: escala horizontalmente. | No se sincroniza entre dispositivos. Se usa POST para una consulta: se justifica porque la lista no cabe de forma razonable en la URL y la operación es segura e idempotente. |

## Decisión
La PWA guarda la canasta en el almacenamiento local del dispositivo (Pinia persistido). La API expone `POST /v1/canastas/comparacion`:
- Recibe de 1 a 20 ítems `{productoId, cantidad, unidad}`, sin duplicados.
- Responde el resultado sin guardar la canasta.
- No registra la canasta en los logs de aplicación.

Cuando exista Identidad, se podrá agregar una canasta en el servidor sin cambiar este endpoint.

## Consecuencias
- **Positivas:** privacidad por construcción (EC-07); sin migraciones; el endpoint es puro y fácil de probar y de cargar con k6.
- **Negativas / riesgos:** se pierde la canasta si se borran los datos del navegador. La respuesta no se puede cachear en un CDN por URL.

## Verificación
- C-14: 422 ante 0 ítems, más de 20, duplicados, cantidad ≤ 0 o una unidad no comparable.
- C-15: la respuesta no contiene campos personales (misma regla que C-07).
- Revisión de la migración: no se crea ninguna tabla de canastas.
