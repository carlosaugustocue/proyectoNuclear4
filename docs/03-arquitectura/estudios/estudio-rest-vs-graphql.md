# Análisis comparativo de paradigmas de comunicación: REST vs GraphQL para una plataforma colaborativa de comparación de precios

**Autores:**
Carlos Augusto Aranzazu · Sofia Giraldo González · Miguel Angel Rodriguez

**Institución:** Corporación Universitaria Empresarial Alexander von Humboldt
**Programa:** Ingeniería de Software — Seminario Nuclear IV
**Asignatura:** Arquitectura de Software II
**Docente:** Rubén Darío Díaz Bautista
**Periodo académico:** II-2026
**Fecha:** noviembre de 2026

> **Decisión documentada:** ADR-0004 — [API REST documentada con OpenAPI 3.1](../adr/0004-api-rest-openapi.md)

---

## Resumen

El presente documento analiza dos paradigmas de comunicación para API — REST (Representational State Transfer) y GraphQL — en el contexto del proyecto de Seminario Nuclear IV: una plataforma colaborativa de comparación de precios entre tiendas y supermercados en Armenia y el departamento del Quindío (Colombia). Se describen los fundamentos teóricos de cada paradigma, se identifican sus ventajas y limitaciones, y se contrastan frente a los escenarios de calidad priorizados del proyecto. El análisis concluye que REST, documentado con OpenAPI 3.1, es la alternativa más adecuada para el contexto específico del proyecto, por su soporte nativo de caché HTTP, su alineación con los patrones arquitectónicos ya adoptados (CQRS ligero con modelo de lectura precalculado), y por las garantías que ofrece sobre la privacidad de los datos de los reportantes (EC-07).

**Palabras clave:** REST, GraphQL, API, arquitectura de software, comparación de precios, calidad del software, OpenAPI

---

## 1. Introducción

El diseño de la capa de comunicación de una API es una de las decisiones arquitectónicas de mayor impacto en la calidad de un sistema de software. Esta decisión determina cómo los clientes obtienen los datos que necesitan, qué tan eficientemente se transmite la información, y qué tan fácil resulta evolucionar la API sin romper los contratos existentes (Bass et al., 2021).

En el marco del Seminario Nuclear IV de Ingeniería de Software, el equipo desarrolla una plataforma colaborativa de comparación de precios cuyos actores son consumidores, reportantes, comercios y moderadores. El sistema presenta características que hacen que la elección del paradigma de comunicación no sea trivial: una marcada asimetría entre lecturas y escrituras, múltiples tipos de cliente con necesidades distintas, conectividad móvil limitada en el contexto de uso principal, y una arquitectura que debe evolucionar para incorporar un módulo comercial en una fase posterior (Díaz Bautista, 2026).

El objetivo de este documento es registrar el estudio comparativo realizado entre REST y GraphQL, documentar los argumentos evaluados, y justificar la decisión arquitectónica adoptada, cumpliendo con el mínimo exigible establecido en la guía del proyecto de presentar las alternativas descartadas con sus razones (Díaz Bautista, 2026, sección 8.1).

---

## 2. Marco teórico

### 2.1 REST (Representational State Transfer)

REST es un estilo arquitectónico propuesto por Fielding (2000) en su disertación doctoral. Define un conjunto de restricciones que, aplicadas al diseño de sistemas hipermedia distribuidos, producen propiedades de rendimiento, escalabilidad, simplicidad, modificabilidad, visibilidad y fiabilidad. Las seis restricciones fundamentales son: cliente-servidor, sin estado (*stateless*), cacheable, interfaz uniforme, sistema en capas y código bajo demanda (opcional).

En la práctica, una API REST organiza los recursos como URLs (`/productos`, `/precios`, `/establecimientos`) y utiliza los métodos HTTP (`GET`, `POST`, `PUT`, `DELETE`, `PATCH`) para operar sobre ellos. La propiedad de ser *cacheable* es especialmente relevante en sistemas de alta lectura: las respuestas de `GET` pueden ser almacenadas por proxies, CDN y navegadores, reduciendo la carga sobre el servidor backend (Kleppmann, 2017). Una API REST puede documentarse con el estándar OpenAPI, que permite generar documentación navegable, mocks y pruebas de contrato automáticas (Richardson et al., 2013).

### 2.2 GraphQL

GraphQL es un lenguaje de consultas para APIs y un entorno de ejecución en el servidor, desarrollado internamente por Facebook (Meta) a partir de 2012 y publicado como especificación abierta en 2015 (GraphQL Foundation, 2015). A diferencia de REST, GraphQL expone un único *endpoint* y permite al cliente especificar exactamente los campos que necesita en cada consulta.

Los conceptos centrales de GraphQL son (Buna, 2021):

- **Schema:** define los tipos y las operaciones disponibles (`Query`, `Mutation`, `Subscription`).
- **Resolvers:** funciones del servidor que resuelven cada campo del *schema*.
- **Query:** operación de lectura en la que el cliente declara exactamente qué datos necesita.
- **Mutation:** operación de escritura o modificación.
- **Subscription:** flujo de datos en tiempo real sobre WebSocket.

GraphQL fue diseñado para resolver los problemas de *overfetching* (recibir más datos de los necesarios) y *underfetching* (necesitar múltiples llamadas para obtener todos los datos requeridos), que son limitaciones comunes en APIs REST no optimizadas para los clientes que las consumen (Richards y Ford, 2020).

---

## 3. Análisis comparativo

### 3.1 Criterios de evaluación

Los criterios de evaluación se derivan directamente de los escenarios de calidad priorizados en la guía del proyecto (Díaz Bautista, 2026) y de las restricciones identificadas durante la fase de comprensión del problema. Se utiliza la priorización por árbol de utilidad del documento de arquitectura v1 como referencia de peso:

| ID | Criterio | Escenario / restricción de origen | Peso |
|---|---|---|---|
| C1 | Rendimiento bajo carga concurrente | EC-04 (500 usuarios, P95 ≤ 2 s) | Alto |
| C2 | Privacidad de datos del reportante | EC-07 (sin datos personales en respuestas públicas) | Alto |
| C3 | Carga mínima con conectividad móvil limitada | EC-01, EC-09 (registro ≤ 15 s, offline-first) | Alto |
| C4 | Evolución sin romper contratos existentes | EC-05 (módulo comercial sobre núcleo) | Medio |
| C5 | Documentación y pruebas de contrato automáticas | Mínimo exigible (guía, sección 8.1) | Medio |
| C6 | Costo operacional para un equipo de tres personas | R-04 (restricción de equipo y semestre) | Alto |

### 3.2 REST frente a los criterios

**C1 — Rendimiento bajo carga:**
Las respuestas de `GET` son cacheables de forma nativa a nivel HTTP. Un proxy inverso (Nginx, Caddy) o una CDN puede atender las consultas de comparación directamente desde caché sin que la solicitud llegue al backend. Para EC-04, esto permite manejar los picos de quincena sin escalar los servidores de aplicación (Kleppmann, 2017). En la arquitectura adoptada, el *endpoint* `GET /comparaciones` lee una tabla `comparacion_vigente` desnormalizada y precalculada (ADR-0003): la respuesta es una lectura indexada de una sola tabla, completamente compatible con la caché HTTP.

**C2 — Privacidad del reportante:**
REST con OpenAPI permite definir con precisión qué campos se incluyen en cada respuesta pública. Los esquemas del contrato (`openapi.yaml`) se diseñaron explícitamente sin incluir identificadores de reportantes ni coordenadas de dispositivos, lo que permite verificar esta propiedad mediante pruebas de contrato automáticas que validan las respuestas contra el esquema (prueba C-07 del plan de calidad). Esta verificación es directa y auditable.

**C3 — Carga mínima en conectividad móvil:**
El escenario EC-09 se resuelve en el cliente mediante una cola local en IndexedDB y un Service Worker (ADR-0006), no reduciendo el tamaño de la respuesta del servidor. Para el registro de precio (`POST /reportes-precio`), la petición lleva únicamente los campos necesarios definidos en el contrato. La arquitectura CQRS con modelo de lectura precalculado (ADR-0003) garantiza que `GET /comparaciones` devuelva exactamente los datos del modelo de presentación sin joins en tiempo de consulta, eliminando el problema de *underfetching* sin necesitar GraphQL.

**C4 — Evolución del contrato:**
La incorporación del módulo comercial en el corte 2-3 añade nuevos recursos (`/perfiles-comercio`, `/promociones`) y extiende recursos existentes con campos opcionales. En REST con OpenAPI, los campos opcionales y la versión en la ruta (`/v1`) permiten que los clientes existentes no se rompan (Richardson et al., 2013). El módulo comercial se integra publicando eventos de dominio que los módulos existentes consumen, sin alterar los contratos ya definidos (ADR-0002, ADR-0003).

**C5 — Documentación y pruebas de contrato:**
OpenAPI 3.1 es el estándar más maduro para documentar APIs REST, con herramientas como Swagger UI, Redoc y Schemathesis para pruebas de contrato automáticas. La verificación de EC-07 se implementa validando automáticamente las respuestas contra el esquema en el pipeline de CI (mínimo exigible de la guía).

**C6 — Costo operacional:**
REST no requiere dependencias adicionales más allá del *framework* web. El equipo puede implementar, probar y desplegar la API con las herramientas que ya conoce. El contrato OpenAPI es un archivo estático que el equipo mantiene junto al código.

### 3.3 GraphQL frente a los criterios

**Ventajas reales de GraphQL en este contexto:**

*Consultas flexibles para múltiples clientes:* La consulta de comparación de precios (producto + precios de múltiples establecimientos + confiabilidad de cada precio) es naturalmente una *query* GraphQL anidada. Un cliente móvil podría solicitar solo los campos que necesita para su pantalla, mientras que el portal web solicita información más completa. Sin embargo, dado que la arquitectura CQRS ya resuelve este problema con una tabla `comparacion_vigente` precalculada, `GET /comparaciones` es una llamada eficiente sin importar el paradigma.

*Evolución aditiva del schema:* GraphQL es aditivo por diseño: añadir tipos y campos no rompe los clientes existentes. Sin embargo, dado que el equipo controla todos los clientes (PWA y consola de moderación) y el calendario de entregas está sincronizado entre ambos, esta ventaja es marginal en el contexto del proyecto.

**Limitaciones de GraphQL en este contexto:**

*Caché HTTP:* Las *queries* GraphQL viajan por `POST` por defecto, y `POST` no es cacheable a nivel HTTP. Aunque existen extensiones como *persisted queries* sobre `GET`, requieren configuración adicional en cliente y servidor. Esta limitación es determinante para EC-04 (Bass et al., 2021).

*Superficie de privacidad (EC-07):* El *schema* de GraphQL expone internamente relaciones entre entidades (usuarios, reportes, establecimientos) que el servidor debe proteger campo por campo mediante directivas de autorización. Esto aumenta el riesgo de exposición accidental de datos personales y la complejidad de las pruebas de seguridad (OWASP, 2023). En un sistema que recibe datos de ubicación de reportantes y los debe proteger explícitamente, este riesgo no es trivial.

*Problema N+1:* Al resolver relaciones anidadas (por ejemplo, los precios de un establecimiento para cada uno de los 20 establecimientos de una comparación), GraphQL ejecuta una consulta a la base de datos por cada elemento si no se implementa el patrón DataLoader (Buna, 2021). Resolver este problema requiere una capa adicional de complejidad que REST con un modelo de lectura precalculado no necesita.

*Curva de aprendizaje:* La configuración de *resolvers*, DataLoader y las herramientas de prueba de GraphQL representa tiempo de aprendizaje que, en el marco de un semestre académico, compite directamente con los entregables de ambas asignaturas.

### 3.4 Tabla comparativa consolidada

| Criterio | Peso | REST + OpenAPI | GraphQL | Ventaja |
|---|---|---|---|---|
| C1 — Rendimiento / caché | Alto | Caché HTTP nativa; `GET` cacheable por CDN o proxy | `POST` por defecto; caché requiere configuración adicional | **REST** |
| C2 — Privacidad del reportante | Alto | Contrato explícito; campos protegidos verificados por prueba de contrato automática | Directivas por campo; mayor superficie; pruebas más complejas | **REST** |
| C3 — Móvil, payload mínimo | Alto | Modelo de lectura precalculado elimina *underfetching*; mismo efecto que GraphQL | Payload exacto por cliente; útil si no hay modelo de lectura | Empate (resuelto por CQRS) |
| C4 — Evolución del contrato | Medio | Campos opcionales + versión en ruta; clientes bajo control del equipo | Aditivo por diseño; más natural para múltiples clientes independientes | Empate |
| C5 — Documentación y pruebas | Medio | OpenAPI 3.1; Schemathesis; herramientas muy maduras | GraphiQL, Apollo Sandbox; pruebas de contrato menos maduras | **REST** |
| C6 — Costo operacional | Alto | Sin dependencias adicionales; sin DataLoader | Apollo/Strawberry + DataLoader; configuración adicional | **REST** |

**Resultado:** REST obtiene ventaja en cuatro de los seis criterios, con los dos criterios de mayor peso (C1 y C2) a su favor. GraphQL no obtiene ventaja clara en ningún criterio dentro del contexto específico de este proyecto.

---

## 4. Decisión y justificación

### 4.1 Decisión adoptada

El equipo adopta **REST con OpenAPI 3.1** como paradigma de comunicación de la API pública de la plataforma (ADR-0004). Esta decisión se confirma tras el estudio comparativo formal documentado en el presente informe.

### 4.2 Justificación estructurada

La decisión se sustenta en la convergencia de tres factores determinantes:

**Factor 1 — La arquitectura CQRS neutraliza la principal ventaja de GraphQL.**
El problema de *underfetching* que GraphQL resuelve con *queries* flexibles ya está resuelto en la arquitectura adoptada mediante el modelo de lectura precalculado (`comparacion_vigente`, ADR-0003). La consulta de comparación — la operación más frecuente y más compleja del sistema — es una lectura indexada de una tabla desnormalizada, no un *join* costoso sobre el modelo de escritura. Esto hace que REST y GraphQL produzcan resultados equivalentes en términos de rendimiento para el caso de uso principal, eliminando la ventaja más significativa de GraphQL en este dominio.

**Factor 2 — La caché HTTP es determinante para EC-04.**
Con 500 usuarios concurrentes en picos de quincena (EC-04, P95 ≤ 2 s), la caché nativa de `GET /comparaciones` en un proxy inverso puede absorber la mayor parte del tráfico sin que la solicitud llegue al servidor de aplicación. GraphQL requeriría un mecanismo de caché a nivel de aplicación (Redis u equivalente) que añade una pieza de infraestructura adicional, especialmente costosa de operar para un equipo de tres personas durante un semestre académico (R-04).

**Factor 3 — La privacidad del reportante (EC-07) es un escenario de calidad explícito y verificable.**
REST con un contrato OpenAPI definido por el equipo garantiza con precisión qué datos se exponen en cada respuesta pública. Verificar esta propiedad es directo: basta con validar las respuestas contra el *schema* en el pipeline de CI. En GraphQL, el control granular de acceso a campos requiere directivas de autorización adicionales y aumenta la superficie de pruebas de seguridad, lo que incrementa el riesgo de EC-07 sin beneficios compensatorios en este contexto (OWASP, 2023).

### 4.3 Condiciones de revisión de la decisión

Esta decisión se revisará en los siguientes escenarios:

1. **Corte 3 — Portal de comercios:** si el portal requiere un contrato de API significativamente distinto al de la PWA del consumidor, se evaluará el patrón Backend for Frontend (BFF). En ese caso, un *endpoint* GraphQL específico para el portal podría coexistir con la API REST del núcleo.
2. **Módulo de alertas (opcional):** si se implementa el módulo de alertas de bajada de precio (identificado como opcional en la guía), se requiere `WebSockets` o `Server-Sent Events` para notificaciones en tiempo real, lo que se registraría en un ADR independiente sin alterar la decisión sobre el paradigma de la API principal.

---

## 5. Conclusiones

El análisis comparativo entre REST y GraphQL, aplicado al contexto específico de la plataforma colaborativa de comparación de precios, demuestra que la decisión óptima depende de las características del dominio y la arquitectura, no del paradigma en abstracto.

GraphQL ofrece ventajas reales en sistemas con múltiples clientes independientes y necesidades heterogéneas de datos, especialmente cuando no existe un modelo de lectura precalculado. Sin embargo, en este proyecto, la combinación de CQRS ligero con tabla `comparacion_vigente` precalculada resuelve el problema de *underfetching* sin necesitar GraphQL, la caché HTTP nativa de REST es determinante para el escenario de rendimiento EC-04, y el escenario de privacidad EC-07 favorece el contrato explícito y auditable de OpenAPI.

La decisión se registra formalmente en el ADR-0004. El equipo reconoce el *trade-off* aceptado: se renuncia a la flexibilidad de consulta de GraphQL a cambio de caché HTTP nativa, un contrato de API más predecible y auditable, menor complejidad operacional, y pruebas de privacidad más directas. Esta renuncia está justificada por las restricciones y escenarios de calidad del proyecto.

---

## Referencias

Bass, L., Clements, P., & Kazman, R. (2021). *Software architecture in practice* (4.ª ed.). Addison-Wesley Professional.

Buna, S. (2021). *Learning GraphQL: Declarative data fetching for modern web apps*. O'Reilly Media.

Díaz Bautista, R. D. (2026). *Guía para la elaboración del proyecto del seminario nuclear* (Seminario Nuclear 4, II-2026). Corporación Universitaria Empresarial Alexander von Humboldt.

Fielding, R. T. (2000). *Architectural styles and the design of network-based software architectures* [Disertación doctoral, University of California, Irvine]. eScholarship. https://www.ics.uci.edu/~fielding/pubs/dissertation/top.htm

GraphQL Foundation. (2015). *GraphQL specification* (edición de octubre de 2021). https://spec.graphql.org/

Kleppmann, M. (2017). *Designing data-intensive applications: The big ideas behind reliable, scalable, and maintainable systems*. O'Reilly Media.

OWASP. (2023). *GraphQL cheat sheet*. OWASP Foundation. https://cheatsheetseries.owasp.org/cheatsheets/GraphQL_Cheat_Sheet.html

Richards, M., & Ford, N. (2020). *Fundamentals of software architecture: An engineering approach*. O'Reilly Media.

Richardson, L., Ruby, S., & Amundsen, M. (2013). *RESTful web APIs: Services for a changing world*. O'Reilly Media.
