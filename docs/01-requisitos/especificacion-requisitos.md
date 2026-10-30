# Especificación de requisitos y calidad de datos — Núcleo v1

> **Entregable 2 de Modelos de Calidad (Corte 1)** · también es insumo del Documento de arquitectura v1.
> Estado: borrador del 30/10/2026. Los valores marcados con ⚑ son propuestas del equipo y deben validarse con la evidencia de la Fase 1 (entrevistas, encuesta y benchmarking).

## 1. Propósito y alcance

Este documento especifica los requisitos del **núcleo** de la plataforma: catálogo, registro colaborativo de precios, directorio de establecimientos y comparación. El **módulo comercial** (perfiles, precios oficiales y promociones) no se especifica aquí; se introduce en el corte 2. Sin embargo, la arquitectura del núcleo debe permitir incorporarlo sin rediseño estructural (EC-05).

**Fuera del alcance:** venta en línea, carrito, pagos, pedidos, domicilios, recomendaciones basadas en perfilamiento y cualquier intermediación comercial.

## 2. Actores del núcleo

| Actor | Autenticación | Interés principal | Riesgo que introduce |
|---|---|---|---|
| Consumidor | Opcional | Saber dónde comprar más barato, con información confiable | Ninguno relevante |
| Reportante | Requerida | Registrar un precio rápido y saber que su aporte sirve | Errores de digitación; reportes malintencionados |
| Moderador | Requerida (rol) | Preservar la integridad de la comparación | Decisiones arbitrarias (requiere trazabilidad) |
| Comercio *(corte 2)* | Requerida (rol) | Publicar precios y corregir información | Conflicto de interés |
| SIPSA – DANE (sistema externo) | N/A | Fuente pública de referencia | Precios mayoristas, no de tienda (ver ADR-0010) |

## 3. Glosario del dominio

| Término | Definición |
|---|---|
| **Producto canónico** | Bien de consumo identificado sin importar su empaque (p. ej., "Arroz blanco Diana"). |
| **Presentación** | Forma concreta de venta: cantidad, unidad y empaque (p. ej., 500 g, 1 kg, paca 6 × 1 L). Puede tener código EAN/GTIN. |
| **Alias** | Denominación alternativa de un producto o presentación, usada para la búsqueda. |
| **Observación de precio** | Precio de una presentación en un establecimiento, en una fecha y con una fuente (reportante, comercio o fuente pública). |
| **Unidad base** | kg, L o unidad. Toda comparación por unidad se expresa en COP por unidad base. |
| **Vigencia** | Periodo durante el cual una observación se puede mostrar como precio actual. |
| **Estado de confianza** | `CONFIABLE`, `POR_VERIFICAR` o `NO_CONFIABLE`. Lo calcula el sistema y puede ajustarlo un moderador. |
| **Corroboración** | Confirmación independiente de una observación por otro reportante. |

## 4. Requisitos funcionales del núcleo

Prioridad MoSCoW: **M** = obligatorio en el corte 2, **S** = debería, **C** = podría.

### 4.1 Catálogo

| ID | Requisito | Prioridad |
|---|---|---|
| RF-01 | El sistema debe permitir buscar productos por nombre, alias o código EAN, con tolerancia a tildes y mayúsculas. | M |
| RF-02 | El sistema debe mostrar el detalle de un producto con sus presentaciones (cantidad, unidad y EAN si existe). | M |
| RF-03 | El reportante debe poder proponer un producto o presentación inexistente; la propuesta queda pendiente de moderación y puede usarse de inmediato en su propio reporte. | S |
| RF-04 | El moderador debe poder fusionar productos duplicados y administrar alias, conservando el historial de precios de ambos registros. | M |

### 4.2 Establecimientos

| ID | Requisito | Prioridad |
|---|---|---|
| RF-05 | El sistema debe mantener un directorio de establecimientos con nombre, tipo (tienda de barrio, minimercado, supermercado), dirección, barrio, municipio y coordenadas. | M |
| RF-06 | El sistema debe listar los establecimientos cercanos a una ubicación dada, dentro de un radio configurable. | M |
| RF-07 | El reportante debe poder proponer un establecimiento nuevo; queda pendiente de moderación. | S |

### 4.3 Registro colaborativo de precios

| ID | Requisito | Prioridad |
|---|---|---|
| RF-08 | El reportante debe poder registrar un precio indicando presentación, establecimiento y valor. La fecha y la hora se asignan automáticamente; la foto de la etiqueta es opcional. | M |
| RF-09 | El registro debe poder hacerse sin conexión: queda en una cola local y se sincroniza al recuperar conectividad, sin duplicarse (EC-09). | M |
| RF-10 | El reportante debe poder confirmar con un solo toque que un precio ya mostrado sigue vigente ("sigue igual"). | M |
| RF-11 | El sistema debe sugerir el establecimiento más cercano según la ubicación del dispositivo, previa autorización del usuario. | S |
| RF-12 | El reportante debe poder consultar sus aportes y su estado (confiable, por verificar, etc.) y cuántas veces fueron consultados. | C |

### 4.4 Comparación

| ID | Requisito | Prioridad |
|---|---|---|
| RF-13 | El sistema debe comparar el precio de un producto entre establecimientos y mostrar, por cada uno: precio, precio por unidad base, fecha de la observación, fuente y estado de confianza. | M |
| RF-14 | La comparación debe incluir presentaciones distintas del mismo producto normalizadas a la unidad base. | M |
| RF-15 | El usuario debe poder filtrar por municipio, barrio o radio de distancia y ordenar por precio por unidad o por distancia. El criterio de orden aplicado debe mostrarse siempre (EC-06). | M |
| RF-16 | Las observaciones fuera de vigencia o `NO_CONFIABLE` no deben presentarse como precio vigente; si se muestran, debe ser con una advertencia explícita (EC-02, EC-03). | M |
| RF-17 | Las consultas de comparación no deben exigir cuenta de usuario. | M |

### 4.5 Confianza y moderación

| ID | Requisito | Prioridad |
|---|---|---|
| RF-18 | El sistema debe calcular automáticamente el estado de confianza de cada observación según las reglas RN-04 a RN-08. | M |
| RF-19 | Cualquier usuario autenticado debe poder marcar un precio como dudoso e indicar un motivo. | M |
| RF-20 | El moderador debe disponer de una cola de casos (observaciones anómalas y marcas de usuarios) y resolverlos: confirmar, invalidar o suspender la cuenta. Cada decisión queda registrada. | M |
| RF-21 | El sistema debe limitar la cantidad de reportes por cuenta en un periodo (RN-08). | M |

### 4.6 Cuentas y datos personales

| ID | Requisito | Prioridad |
|---|---|---|
| RF-22 | El registro de cuenta debe solicitar la autorización expresa para el tratamiento de datos personales y enlazar la política de tratamiento (Ley 1581 de 2012). | M |
| RF-23 | El titular debe poder consultar, actualizar y eliminar sus datos personales; sus aportes se conservan anonimizados. | S |
| RF-24 | El sistema debe manejar los roles consumidor, reportante y moderador (y comercio desde el corte 3). | M |

### 4.7 Datos iniciales

| ID | Requisito | Prioridad |
|---|---|---|
| RF-25 | El sistema debe importar periódicamente precios de referencia del SIPSA y mostrarlos como **referencia mayorista**, separados de los precios de tienda (ADR-0010). | S |
| RF-26 | El sistema debe permitir cargar en lote un catálogo y un directorio semilla (CSV) preparados por el equipo. | M |

## 5. Reglas de negocio (candidatas a TDD)

Estas reglas son el objetivo principal del desarrollo guiado por pruebas en el corte 2. Cada una debe tener su suite de pruebas escrita **antes** de su implementación.

| ID | Regla | Ejemplo de caso de prueba |
|---|---|---|
| RN-01 | **Normalización por unidad.** `precio_unidad = precio / (cantidad × unidades_por_empaque × factor_a_base)`. Factores: g→kg 0,001; mL→L 0,001; kg y L 1; unidad 1. | Paca 6 × 330 mL a $9.900 → $5.000/L |
| RN-02 | **Precisión.** Los cálculos usan decimales exactos (no coma flotante). Se muestra en COP redondeado al peso y nunca se redondean resultados intermedios. | $2.990 / 0,454 kg = $6.586/kg |
| RN-03 | **Comparabilidad.** Solo se comparan por unidad presentaciones con la misma unidad base; mezclar kg con unidad produce un error de dominio. | Huevos (unidad) frente a arroz (kg) → error |
| RN-04 | **Vigencia por categoría** ⚑. Perecederos: 3 días; abarrotes y aseo: 7 días. Entre 1× y 2× la vigencia se muestra con la advertencia "puede estar desactualizado"; después de 2× pasa a historial. | Arroz reportado hace 9 días → advertencia |
| RN-05 | **Corroboración** ⚑. Si dos o más reportantes independientes registran precios con diferencia ≤ 5 % en 48 h, la observación pasa a `CONFIABLE`. | Dos reportes de $3.000 y $3.100 → confiable |
| RN-06 | **Anomalía estadística** ⚑. Se calcula el puntaje z modificado `M = 0,6745·(p − mediana)/MAD` frente a las observaciones vigentes de la misma presentación en el municipio. Si \|M\| > 3,5, la observación queda `POR_VERIFICAR` y entra a la cola de moderación. Requiere n ≥ 5; si n < 5, se compara con el historial del mismo establecimiento (±50 %). | Mediana $3.000; reporte de $300 → por verificar |
| RN-07 | **Peso por reputación** ⚑. Las cuentas con menos de 7 días o menos de 5 aportes confirmados no pueden, por sí solas, cambiar el precio vigente de un establecimiento; requieren corroboración. | Cuenta nueva reporta $1.000 → no reemplaza el vigente |
| RN-08 | **Límites de aporte** ⚑. Máximo 30 reportes por hora por cuenta. Por presentación, establecimiento y día cuenta solo el último reporte de cada usuario. | Tercer reporte del mismo producto el mismo día → reemplaza el anterior |
| RN-09 | **Precio vigente mostrado.** Es la observación más reciente en vigencia que no sea `NO_CONFIABLE`. Si es `POR_VERIFICAR`, se muestra con advertencia. | — |
| RN-10 | **Orden neutral.** Por defecto se ordena por precio por unidad ascendente; también puede ordenarse por distancia. Ante un empate gana la observación más reciente. Ningún atributo comercial interviene en el orden. | Dos tiendas al mismo precio → primero la más reciente |

## 6. Requisitos no funcionales

| ID | Requisito | Característica (ISO/IEC 25010:2023 / 25012) | Escenario |
|---|---|---|---|
| RNF-01 | Registro de un precio en ≤ 15 s desde la góndola, con conectividad limitada | Capacidad de interacción – operabilidad | EC-01 |
| RNF-02 | Retiro del precio anterior como vigente dentro del plazo definido | Actualidad (25012) | EC-02 |
| RNF-03 | Detección de reportes anómalos con exhaustividad y falsos positivos acotados | Credibilidad (25012); Seguridad – integridad y resistencia | EC-03 |
| RNF-04 | p95 ≤ 2 s en consultas de comparación con 500 usuarios concurrentes | Eficiencia de desempeño – comportamiento temporal y capacidad | EC-04 |
| RNF-05 | Incorporación del módulo comercial sin rediseño estructural del núcleo | Mantenibilidad – modularidad y modificabilidad | EC-05 |
| RNF-06 | Orden de la comparación independiente de relaciones comerciales | Adecuación funcional – corrección funcional | EC-06 |
| RNF-07 | Ubicación y actividad del reportante no expuestas públicamente | Seguridad – confidencialidad; Confidencialidad (25012) | EC-07 |
| RNF-08 | Interfaces conformes a WCAG 2.2 nivel AA en los flujos críticos | Capacidad de interacción – inclusividad | EC-08 |
| RNF-09 | Ningún registro hecho sin conexión se pierde ni se duplica | Fiabilidad – tolerancia a fallos | EC-09 |
| RNF-10 | Reglas de negocio desarrolladas con TDD y con regresiones detectables automáticamente | Mantenibilidad – capacidad de ser probado | EC-10 |
| RNF-11 | Toda observación conserva su origen (fuente, fecha, cuenta seudonimizada) y las decisiones de moderación quedan registradas | Trazabilidad (25012); Seguridad – responsabilidad | EC-03 |

## 7. Restricciones

| ID | Restricción | Origen |
|---|---|---|
| R-01 | Cumplimiento de la Ley 1581 de 2012 y del Decreto 1074 de 2015 (datos personales) | Guía §12 |
| R-02 | Ley 1480 de 2011 (información de precios y promociones) | Guía §12 |
| R-03 | La plataforma no debe facilitar la coordinación de precios entre competidores (Ley 155 de 1959 y Ley 1340 de 2009): no se ofrecen a los comercios vistas agregadas de precios de competidores con fines de fijación | Guía §12 |
| R-04 | Equipo de 3 personas, un semestre y presupuesto de nube de nivel gratuito o académico | Contexto del curso |
| R-05 | Documentación obligatoria: C4, ADRs, API documentada (OpenAPI), TDD, repositorio Git y despliegue reproducible | Guía §8.1 |
| R-06 | Respeto de los términos de uso si se obtienen datos de sitios de terceros (no se hará *scraping* de catálogos de cadenas sin autorización) | Guía §12 |

## 8. Escenarios de calidad refinados

Formato de seis partes de Bass, Clements y Kazman (2021): fuente, estímulo, artefacto, entorno, respuesta y medida de la respuesta. Los escenarios EC-01 a EC-06 refinan los de la guía; los escenarios EC-07 a EC-10 son nuevos y salen del análisis del equipo.

| ID | Fuente | Estímulo | Artefacto | Entorno | Respuesta | Medida |
|---|---|---|---|---|---|---|
| EC-01 | Reportante | Registra el precio de un producto | Aplicación cliente (PWA) | Frente a la góndola, red móvil 3G degradada (≈ 400 kbps, 300 ms de latencia) | El registro queda guardado (en el servidor o en la cola local) y se confirma al usuario | ≥ 80 % de los intentos en ≤ 15 s en una prueba con ≥ 8 usuarios |
| EC-02 | Comercio *(o reportante en el núcleo)* | Cambia el precio de un producto | Modelo de lectura de la comparación | Operación normal | La comparación deja de mostrar el precio anterior como vigente | ≤ 5 min (aceptable) / ≤ 1 min (objetivo) ⚑ |
| EC-03 | Usuario malintencionado o grupo coordinado | Registra precios falsos de un establecimiento | Módulo de confianza | Operación normal; conjunto de prueba etiquetado con ≥ 200 reportes, de los cuales ≥ 20 % son anómalos | Los reportes anómalos no se muestran como vigentes sin advertencia y entran a la cola de moderación | Exhaustividad ≥ 80 % y falsos positivos ≤ 5 % ⚑ |
| EC-04 | 500 usuarios concurrentes | Consultan comparaciones | API y modelo de lectura | Pico de fin de semana de quincena; catálogo de ≥ 2.000 presentaciones y ≥ 300 establecimientos | Responde con resultados correctos | p95 ≤ 2 s y tasa de error < 1 % |
| EC-05 | Equipo de desarrollo | Incorpora el módulo comercial | Código fuente del núcleo | Corte 3 | El módulo se agrega como un contexto nuevo; el núcleo solo cambia en puntos de extensión | 0 cambios estructurales; esfuerzo real / estimado ≤ 1,3 |
| EC-06 | Comercio | Publica una promoción | Módulo de comparación | Operación normal | El orden responde solo a los criterios declarados; la promoción aparece en un bloque diferenciado | 100 % de los casos de prueba |
| **EC-07** | Usuario o tercero | Consulta la API o la interfaz buscando quién reportó qué y desde dónde | API pública | Operación normal | No se expone la identidad, la ubicación del dispositivo ni la actividad de los reportantes | 0 campos de datos personales en respuestas públicas (prueba automatizada de contrato) |
| **EC-08** | Usuario con discapacidad visual o motriz | Usa los flujos de consulta y registro con lector de pantalla o teclado | Interfaz de usuario | Navegador móvil | Puede completar ambos flujos | 0 violaciones A/AA en el análisis automatizado (axe) y lista manual WCAG 2.2 AA aprobada |
| **EC-09** | Reportante | Registra 3 precios sin conexión y luego se reconecta | PWA y API de registro | Conectividad intermitente | Los registros se sincronizan automáticamente | 100 % sincronizados, 0 duplicados (claves de idempotencia) |
| **EC-10** | Desarrollador | Introduce un error en una regla de negocio (RN-01 a RN-10) | Suite de pruebas | Integración continua | La suite falla antes del *merge* | Cobertura de líneas del dominio ≥ 80 % (objetivo 90 %); la suite de dominio corre en < 60 s |

### 8.1 Priorización: árbol de utilidad

Cada escenario se califica con (importancia para el negocio, dificultad arquitectónica) en una escala Alta/Media/Baja, siguiendo el método ATAM. Los escenarios (A, A) guían la arquitectura.

```
Utilidad
├── Credibilidad de los datos
│   ├── EC-03 Reportes falsos ................................. (A, A)  ★
│   └── EC-02 Actualidad del precio ............................ (A, M)  ★
├── Desempeño
│   └── EC-04 500 usuarios concurrentes, p95 ≤ 2 s .............. (A, M)  ★
├── Capacidad de interacción
│   ├── EC-01 Registro en ≤ 15 s ............................... (A, M)  ★
│   └── EC-08 Accesibilidad WCAG 2.2 AA ......................... (M, B)
├── Fiabilidad
│   └── EC-09 Registro sin conexión ............................. (A, A)  ★
├── Mantenibilidad
│   ├── EC-05 Incorporar el módulo comercial ..................... (A, A)  ★
│   └── EC-10 Regresiones detectadas por TDD ..................... (M, B)
├── Adecuación funcional
│   └── EC-06 Neutralidad ....................................... (A, B)
└── Seguridad
    └── EC-07 Privacidad del reportante .......................... (A, M)
```

**Escenarios que guían la arquitectura (★):** EC-03, EC-05, EC-09, EC-04, EC-02 y EC-01.

## 9. Calidad de los datos (ISO/IEC 25012)

| Característica | Tipo | Prioridad | Por qué importa | Cómo se asegura | Medida |
|---|---|---|---|---|---|
| Credibilidad | Inherente | Alta | Las fuentes tienen confiabilidad e intereses distintos | RN-05 a RN-07, moderación | Exhaustividad y falsos positivos (EC-03) |
| Actualidad | Inherente | Alta | Los precios cambian; uno viejo engaña | RN-04, proyección ≤ 5 min | % de precios mostrados como vigentes con antigüedad > vigencia (meta 0 %) |
| Exactitud | Inherente | Alta | El precio debe coincidir con el de la góndola | Foto opcional, corroboración, validación de rangos | % de observaciones auditadas que coinciden en una muestra de campo ⚑ ≥ 95 % |
| Consistencia | Inherente | Alta | El mismo producto debe reconocerse bajo distintas denominaciones | Producto canónico, EAN y alias (ADR-0008) | % de duplicados detectados en el catálogo (meta ≤ 2 %) |
| Completitud | Inherente | Media | Sin cantidad o unidad no se puede normalizar | Campos obligatorios en presentación | % de presentaciones con cantidad, unidad y unidad base (meta 100 %) |
| Precisión | Inherente y del sistema | Media | La normalización exige cantidades exactas | Tipos decimales, RN-02 | Pruebas de RN-01 y RN-02 (100 % aprobadas) |
| Trazabilidad | Inherente y del sistema | Media | Se debe saber de dónde salió cada precio y quién modera | Fuente obligatoria; bitácora de moderación | % de observaciones con fuente y fecha (meta 100 %) |
| Confidencialidad | Inherente y del sistema | Alta | Ubicación y actividad del reportante (Ley 1581) | Seudónimos, sin geolocalización persistida del dispositivo | EC-07 |
| Comprensibilidad | Inherente y del sistema | Media | El consumidor debe entender fuente, fecha y advertencias | Etiquetas visibles en la comparación | Prueba de usabilidad (corte 2) |

## 10. Respuesta a las preguntas de diseño abiertas (guía §4.4)

| # | Pregunta | Postura inicial del equipo | Dónde se decide |
|---|---|---|---|
| 1 | Precio oficial frente a reportado | Se muestra el oficial etiquetado como "publicado por el comercio". Si hay ≥ 2 reportes independientes y recientes que difieren en más del 5 %, se muestran ambos con una advertencia. *(Se implementa en el corte 3.)* | ADR-0007 (se amplía en el corte 2) |
| 2 | ¿Cuándo deja de ser confiable un precio? | Vigencia por categoría (RN-04) | ADR-0007 |
| 3 | Reportes erróneos o malintencionados | Detección estadística, reputación, límites de aporte, corroboración y moderación; se marcan, no se borran | ADR-0007 |
| 4 | ¿Mismo producto? ¿Presentaciones distintas? | Producto canónico + presentación + EAN + alias; normalización a unidad base | ADR-0008 |
| 5 | Primeros datos | Carga semilla del equipo (recorridos de campo en Armenia), SIPSA como referencia mayorista separada | ADR-0010 |
| 6 | Comercio pequeño sin POS | En el núcleo, los reportantes cubren a la tienda. En el corte 3: actualización masiva desde la lista de "mis productos" con un toque por producto | Corte 2 (análisis de impacto) |
| 7 | Neutralidad frente a promociones | La comparación no depende del módulo comercial; promociones en un bloque separado; prueba de arquitectura | ADR-0009 |
| 8 | ¿Qué responde de inmediato y qué puede retrasarse? | Inmediato: confirmación del registro y consulta. Diferido (≤ 5 min): proyección de la comparación, estado de confianza, importación SIPSA | ADR-0003 |
