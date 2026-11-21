# ADR-0023: Comercial se integra con el núcleo por los puntos de extensión existentes; el núcleo nunca depende de Comercial

- **Estado:** Propuesto · **Fecha:** 2026-11-21
- **Responsable(s):** equipo (arquitectura: Miguel Ángel Rodríguez)
- **Escenarios relacionados:** EC-03, EC-05, EC-06, EC-11
- **Relacionado con:** ADR-0009 (neutralidad), ADR-0014 (Comercial como contexto), ADR-0015 (precio oficial), ADR-0016 (fronteras), ADR-0019 (promociones fuera del total), ADR-0022 (credencial)
- **Amplía:** la lista de dependencias permitidas de Comercial en ADR-0014 (agrega Comparación y Moderación)

## Contexto
El análisis de impacto del corte 2 (`analisis-impacto-modulo-comercial.md`, versión del 2026-11-11) proponía integrar Comercial con eventos nuevos (`PrecioOficialPublicado`, `PromocionPublicada`), un listener en Confianza que marcaba el precio oficial `CONFIABLE` sin más y una tabla de lectura `promocion_vigente`. Después, ADR-0015 decidió que el precio oficial **no** recibe confianza automática y entra por el mismo flujo que un reporte. El análisis quedó contradiciendo a ADR-0015 y al código. Este ADR fija cómo se conecta Comercial con cada contexto del núcleo, de modo que:

- AR-02 siga verificado por estructura: Deptrac impide que Comparación, Confianza o Precios importen Comercial.
- EC-05 se cumpla: el núcleo solo cambia en puntos de extensión ya previstos.
- EC-06 se cumpla: las promociones nunca alteran el orden ni el total.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| A. Eventos nuevos de Comercial escuchados por Confianza y Comparación (análisis del corte 2) | Desacople temporal | Confianza y Comparación tendrían que **importar** los eventos de Comercial: viola AR-02 y la regla Deptrac vigente. Además duplica el flujo de confianza que ADR-0015 quiere reutilizar |
| B. Comercial escribe directamente en `comparacion_vigente` y en tablas del núcleo | Rápido | Rompe AR-01 (un contexto escribe tablas ajenas) y salta RN-05 a RN-07 |
| **C. Puertos: Comercial usa la interfaz pública del núcleo y el núcleo declara interfaces que Comercial implementa** | Respeta AR-01 y AR-02 por estructura; reutiliza el flujo de confianza; el núcleo funciona igual sin Comercial | Una interfaz y un adaptador más; la inversión de dependencia hay que explicarla |

## Decisión
Se adopta **C**, con una regla por contexto:

| Contexto del núcleo | Cómo se integra Comercial | Dirección de la dependencia | ¿Cambia el núcleo? |
|---|---|---|---|
| **Precios** | El precio oficial se registra con el caso de uso existente `RegistrarPrecio`, con `FuenteObservacion::COMERCIO` (el valor ya existe). Desde ahí sigue el flujo normal: `PrecioRegistrado` → Confianza (RN-05 a RN-07) → proyección `comparacion_vigente` | Comercial → Precios | No |
| **Confianza** | Nada nuevo: evalúa el precio oficial como cualquier observación. Si difiere en más del 5 % de reportes recientes independientes, queda `POR_VERIFICAR` y abre un caso (ADR-0015) | — | No |
| **Comparación** | Comparación declara la interfaz `PromocionesVigentes` en su capa de aplicación, con una implementación vacía por defecto. Comercial la implementa en su infraestructura y el contenedor la enlaza. `ConsultarComparacion` llena `contenidoPromocional`, campo reservado desde el contrato 0.2.0 (ADR-0009), y **no** pasa promociones a `OrdenComparacion` | Comercial → Comparación (implementa su puerto) | Sí, en un punto de extensión previsto: una interfaz nueva y el llenado de un campo reservado |
| **Canasta** | Usa el mismo puerto para mostrar promociones de los productos de la canasta en `contenidoPromocional`, fuera del total (ADR-0019) | Canasta → Comparación | Sí, en un punto de extensión previsto |
| **Moderación** | La solicitud de corrección (RF-34) abre un caso con un caso de uso público nuevo de Moderación, `AbrirCasoPorCorreccion`, junto a `AbrirCasoPorAnomalia` | Comercial → Moderación | Sí: un caso de uso nuevo; no se modifica ninguno existente |

Dependencias de Comercial en Deptrac: `[Compartido, Catalogo, Establecimientos, Precios, Comparacion, Moderacion]`. Ningún contexto agrega a Comercial en su lista (AR-02).

Contrato público de las promociones (en `contenidoPromocional`): `tipo`, `descripcion`, `establecimiento{id, nombre}`, `productoId`, `vigenteHasta` y la etiqueta fija `esPromocion: true`. No se exponen datos de contacto del perfil (EC-07).

## Consecuencias
- **Positivas:**
  - Desaparecen los dos listeners y la tabla `promocion_vigente` del análisis del corte 2.
  - La confianza del precio oficial queda bajo las mismas reglas probadas (U-05 a U-07).
  - Sin Comercial instalado, el núcleo sigue funcionando: el puerto devuelve una lista vacía. Esa es la prueba más clara de que no depende de Comercial.
- **Negativas:**
  - El núcleo sí recibe cambios pequeños en puntos de extensión: una interfaz y el llenado de un campo en Comparación, y un caso de uso nuevo en Moderación. Se contarán honestamente en la segunda medición de EC-05. M-07 cuenta cambios **estructurales** (0 esperados); los cambios en puntos de extensión se listan aparte.
  - Las promociones se leen en cada consulta (sin proyección). Con pocos comercios es barato; si K-01 o K-02 muestran costo, se proyectan después sin cambiar el contrato.
- **Seguimiento:** pasar a Aceptado cuando el equipo lo revise; verificar con Deptrac (AR-01, AR-02) y con la prueba de contrato que una promoción no cambia `items` ni su orden (C-06).
