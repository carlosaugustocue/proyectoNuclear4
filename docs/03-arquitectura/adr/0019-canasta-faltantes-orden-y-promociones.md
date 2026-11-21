# ADR-0019: Faltantes explícitos, orden por criterios declarados y promociones fuera del total de la canasta

- **Estado:** Aceptado · **Fecha:** 2026-11-21
- **Responsable(s):** equipo (calidad: Sofía Giraldo González)
- **Escenarios relacionados:** EC-11, EC-06, AR-02

## Contexto
El Anexo exige señalar los productos sin precio vigente **sin sumarlos como cero** y ordenar los establecimientos según criterios declarados. También pregunta cómo aplicar las promociones «pague X lleve Y». ADR-0009 (neutralidad) prohíbe que el contenido comercial altere el orden.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Sumar como cero los faltantes y ordenar por total | Simple | Un establecimiento con pocos productos parece el más barato: es engañoso y prohibido por el Anexo |
| Imputar el faltante con el precio promedio de la ciudad | Totales comparables | Inventa precios: viola la credibilidad y la trazabilidad (ISO/IEC 25012) |
| Excluir los establecimientos incompletos | Comparación «justa» | Oculta información útil (la tienda de barrio rara vez tiene los 20 productos): afecta la equidad entre comercios (guía §12) |
| **Faltantes explícitos y dos criterios declarados (`disponibilidad` por defecto, `total`)** | Transparente, neutral y no inventa datos | El usuario debe interpretar dos cifras (total y disponibles) |

Para las promociones: aplicarlas al total haría que el contenido comercial cambiara el orden (viola ADR-0009). Mostrarlas aparte conserva la neutralidad.

## Decisión
- **Total:** suma de los subtotales de los productos con precio vigente. Los faltantes se listan en `faltantes` y nunca entran en la suma. Se informan `disponibles`, `totalProductos` y `completa`.
- **Precio usado:** la presentación vigente de menor precio por unidad base del establecimiento.
- **Orden:**
  - `disponibilidad` (por defecto): más disponibles primero → menor total → nombre.
  - `total`: menor total → más disponibles → nombre.
  - La respuesta siempre incluye `criterioOrden` con su descripción.
- Solo entran los establecimientos con al menos un producto disponible.
- **Promociones:** no modifican el total ni el orden. Irán en `contenidoPromocional` (vacío hasta que Comercial publique), con el ahorro potencial calculado aparte.

## Consecuencias
- **Positivas:** neutralidad verificable (AR-02 se conserva: Canasta no depende de Comercial); no se inventan datos.
- **Negativas / riesgos:** un usuario podría preferir un «total estimado». Se reevaluará con la prueba de usabilidad.

## Verificación
- U-11: nunca suma faltantes y usa la presentación de menor precio.
- U-12: los dos criterios y los desempates.
- C-14: `criterioOrden` presente y `contenidoPromocional` separado.
- K-02: 100 % de los faltantes señalados bajo carga (M-17).
