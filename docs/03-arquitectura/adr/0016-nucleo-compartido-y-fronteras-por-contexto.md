# ADR-0016: Núcleo compartido y fronteras verificadas por contexto delimitado

- Estado: Aceptado
- Fecha: 2026-11-16
- Escenarios relacionados: EC-05, EC-10, AR-01, AR-02

## Contexto

En el corte 2 las diez reglas de negocio (U-01 a U-10) se implementaron dentro de `app/Domains/Precios`,
aunque el diseño (documento de arquitectura v1, §3) las asigna a Confianza y Comparación. `deptrac.yaml`
solo controlaba capas (Domain/Application/Infrastructure/Http), de modo que AR-01 («un módulo solo usa la
interfaz pública de otro») y AR-02 («Comparación no depende de Comercial») no se verificaban de verdad.
Además, varias piezas (dinero, unidades, coordenadas) las usan todos los contextos.

## Alternativas consideradas

| Alternativa | Ventajas | Desventajas |
|---|---|---|
| Dejar todo en Precios y documentar la desviación | Sin trabajo de código | El diseño y el código divergen; AR-01/AR-02 siguen sin verificarse; EC-05 se vuelve más difícil de demostrar |
| Duplicar dinero y unidades en cada contexto | Contextos totalmente independientes | Duplicación de reglas sensibles (RN-01, RN-02): un arreglo en un lado no llega al otro |
| **Mover cada regla a su contexto y extraer un núcleo compartido (Shared Kernel) mínimo** | El código refleja el diseño; Deptrac verifica fronteras reales | Más archivos y un refactor con riesgo (mitigado: las 67 pruebas existentes siguieron en verde) |

## Decisión

- `app/Domains/Compartido` contiene solo valores y servicios sin dependencias: `Precio`, `Dinero`,
  `CantidadNormalizada`, `NormalizadorUnidad`, `Comparabilidad`, `Coordenadas`, `CalculadoraPrecioUnitario`.
- Las reglas RN-04 a RN-07 y RN-09 pasan a `Confianza`; RN-10 (`OrdenComparacion`) pasa a `Comparacion`;
  RN-08 se queda en `Precios`.
- `backend/deptrac.yaml` define una capa por contexto y la lista de dependencias permitidas
  (p. ej., `Comparacion: [Compartido, Confianza, Catalogo, Establecimientos]`, sin `Comercial`).
  Las reglas por capa se conservan en `deptrac-capas.yaml`. El CI ejecuta ambas.
- Los contextos se comunican por eventos de dominio en la cola `domain-events` (`PrecioRegistrado` →
  `ConfianzaEvaluada`) o por los servicios de aplicación públicos (`ConsultarCatalogo`, `ConsultarDirectorio`).

## Consecuencias

- Positivas: AR-01 y AR-02 se verifican automáticamente (0 violaciones, comprobado además con un archivo de
  prueba que introducía la dependencia prohibida y que Deptrac rechazó). EC-05 se puede demostrar en el corte 3
  con el mismo mecanismo.
- Negativas: el núcleo compartido es un punto de acoplamiento; cualquier cambio en él afecta a todos los
  contextos. Se mantiene pequeño a propósito y cubierto al 100 % por pruebas unitarias en sus servicios críticos.
