# ADR-0017: La canasta es un contexto delimitado nuevo que calcula el total en cada consulta sobre el modelo de lectura

- **Estado:** Aceptado · **Fecha:** 2026-11-21
- **Responsable(s):** equipo (arquitectura: Miguel Ángel Rodríguez)
- **Escenarios relacionados:** EC-11 (EC-07 del Anexo 1), EC-02, EC-04, EC-05, EC-07

## Contexto
El Anexo 1 (opción A1) pide calcular el total de una canasta de hasta 20 productos por establecimiento. Las fuerzas en juego:
- EC-11 exige un p95 ≤ 3 s con 200 usuarios concurrentes.
- EC-02 exige que un cambio de precio deje de mostrarse como vigente en ≤ 5 min.
- EC-05 exige agregar la funcionalidad sin cambios estructurales en el núcleo.

El modelo de lectura `comparacion_vigente` (ADR-0003) ya tiene una fila por presentación y establecimiento, con su precio por unidad base.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Ampliar `ConsultarComparacion` (contexto Comparación) con un modo «canasta» | Sin contexto nuevo | Modifica una clase del núcleo (cuenta como cambio estructural para M-07) y mezcla dos casos de uso |
| Contexto nuevo con **precálculo** de totales por canasta (proyección al recibir `ConfianzaEvaluada`) | Latencia de lectura mínima | Las canastas son arbitrarias: no se pueden precalcular sin persistirlas. Agrega una segunda proyección con retraso propio, que alarga la latencia de EC-02. Exige guardar las canastas (problema de privacidad, EC-07). |
| **Contexto nuevo `Canasta` que calcula en cada consulta** a partir de los precios vigentes que expone Comparación | No toca las clases del núcleo. Hereda la actualidad del modelo de lectura. Sin estado. | El cálculo se repite en cada consulta: costo O(productos × establecimientos), acotado por el límite de 20 productos |

## Decisión
Crearemos `app/Domains/Canasta` (Domain, Application e Infrastructure):
- Calcula el total en cada consulta.
- Obtiene los precios **ya evaluados como vigentes** (RN-04, RN-09) a través de un servicio público nuevo de Comparación, `ConsultarPreciosVigentes`, que es el único punto de extensión del núcleo.
- Accede a él mediante un puerto propio, `FuentePreciosVigentes`.
- Deptrac declara `Canasta: [Compartido, Comparacion]`.

## Consecuencias
- **Positivas:**
  - La canasta reutiliza la vigencia, la confianza y la normalización sin duplicarlas.
  - Muestra la misma actualidad que la comparación individual (EC-02).
  - El núcleo solo gana una clase nueva.
- **Negativas / riesgos:**
  - La latencia crece con el número de establecimientos con oferta (punto de sensibilidad). Si K-02 no cumple, la siguiente decisión sería cachear la respuesta por combinación de productos durante 30 s, igual que la comparación (`Cache-Control`).
  - Comparación expone un servicio más que deberá mantener.

## Verificación
- K-02 (k6, 200 VU, canastas de 20 productos, ≥ 300 establecimientos): p95 ≤ 3 s y errores < 1 % (M-16).
- Deptrac con 0 violaciones.
- Diff del núcleo limitado a la clase nueva (M-07 = 0).
