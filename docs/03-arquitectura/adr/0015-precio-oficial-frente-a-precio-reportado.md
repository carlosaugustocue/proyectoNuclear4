# ADR-0015: Precio oficial del comercio frente a precio reportado

- Estado: Propuesto
- Fecha: 2026-11-16
- Escenarios relacionados: EC-03, EC-06, RN-05, RN-09

## Contexto

La pregunta abierta 1 de la especificación de requisitos plantea qué hacer cuando un comercio publica un
precio oficial y los reportantes observan otro. En el corte 3 el módulo comercial permitirá publicar precios
oficiales. Hay que decidir, antes de construirlo, cómo conviven ambos en el modelo de lectura sin que el
comercio pueda imponer un precio que la comunidad desmiente, y sin que su relación comercial lo favorezca
en el orden (ADR-0009).

## Alternativas consideradas

| Alternativa | Ventajas | Desventajas |
|---|---|---|
| El precio oficial siempre prevalece | Simple; incentiva a los comercios a participar | Un comercio podría publicar un precio falso más bajo para subir posiciones; contradice EC-03 |
| El precio oficial se ignora en la comparación | Neutralidad máxima | Desperdicia la fuente más directa y desincentiva la participación de los comercios |
| **El precio oficial es una observación más, con fuente `COMERCIO`, que pasa por las mismas reglas de confianza** | Una sola ruta de datos (`RegistroPrecio` + `ConfianzaEvaluada`); la fuente se muestra al usuario; se puede contradecir con reportes | Requiere definir cómo pesa la fuente dentro de la corroboración |

## Decisión

El precio oficial **entra por el mismo flujo que un reporte** (`RegistroPrecio` con
`FuenteObservacion::COMERCIO`; el enum ya existe) y se evalúa con las mismas reglas RN-05 a RN-07:

- Se muestra con la etiqueta «Precio oficial» (la PWA ya distingue `fuente`), nunca con una posición distinta:
  el orden sigue dependiendo solo del criterio declarado (RN-10).
- Un precio oficial que difiere en más del 5 % de dos o más reportes recientes e independientes queda
  POR_VERIFICAR y abre un caso de moderación (mismo mecanismo de RN-06).
- Las **promociones** no son precios: se publican en `contenidoPromocional`, separado de `items`
  (EC-06; el campo ya existe y hoy viaja vacío).

## Consecuencias

- Positivas: ningún cambio estructural al núcleo (EC-05). El comercio solo aporta un dato más, verificable.
- Negativas: un comercio honesto puede ver su precio en verificación si los reportes están desactualizados;
  la moderación debe resolverlo. Hay que medir esa tasa en el corte 3.
- Pendiente: decidir con evidencia de campo (Fase 1, pregunta 2 a comerciantes) si un precio oficial
  confirmado debe tener una vigencia más larga que uno reportado.

## Nota de revisión (2026-11-21, al construir el módulo)

La prueba de integración I-08 mostró que el segundo punto de la decisión describía mal el comportamiento de Confianza. **La decisión no cambia**: el precio oficial entra por el mismo flujo y las mismas reglas. Lo que cambia es la descripción de su efecto:

| Situación del precio oficial | Estado real | Regla |
|---|---|---|
| Coincide (diferencia ≤ 5 % en 48 h) con un reporte de otro autor | `CONFIABLE` | RN-05 |
| Difiere más del 5 % de los reportes, pero no es atípico | `NO_CORROBORADO`: se muestra, pero no como confiable | RN-05 |
| Es atípico (\|M\| > 3,5 con n ≥ 5, o más de ±50 % del historial del establecimiento con n < 5) | `POR_VERIFICAR` y abre un caso de moderación | RN-06 |

Por eso M-22 mide la tasa de precios oficiales que **no** quedan `CONFIABLE`, y no solo los que quedan `POR_VERIFICAR`.

