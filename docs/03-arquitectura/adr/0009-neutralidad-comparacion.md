# ADR-0009: Neutralidad de la comparación garantizada por diseño

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-06, EC-05, pregunta de diseño 7, guía §12.1 y §12.3

## Contexto
Cuando lleguen las promociones (corte 3), existe el riesgo de que el contenido comercial altere el orden de la comparación o se confunda con ella. Decidirlo ahora evita un rediseño después.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Regla de negocio "no ordenar por patrocinio" sin control técnico | Simple | Fácil de romper sin darse cuenta |
| **Separación estructural + criterio de orden explícito + prueba de arquitectura** | Verificable automáticamente | Algo más de diseño |

## Decisión
1. El módulo **Comparación no depende** del módulo Comercial ni conoce atributos de relación comercial; lo verifica la prueba AR-02.
2. El orden solo admite los criterios declarados (`precio_unidad`, `distancia`), y la respuesta siempre incluye `criterioOrden`.
3. El contenido promocional se devolverá en un campo **separado** (`contenidoPromocional`) y se mostrará en un bloque visualmente diferenciado con la etiqueta "Promoción".

## Consecuencias
- **Positivas:** EC-06 verificable al 100 %; argumento sólido frente a la Ley 1480 y a la equidad entre comercios.
- **Negativas:** los comercios no pueden "pagar por aparecer primero" (es intencional).
- **Verificación:** U-10, C-06, AR-02.
