# ADR-0002: Estilo arquitectónico — monolito modular organizado por contextos delimitados

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-05, EC-03, EC-04, R-04

## Contexto
El núcleo debe evolucionar para recibir el módulo comercial sin rediseño (EC-05). La carga esperada es modesta (500 usuarios concurrentes, EC-04). El equipo es de 3 personas con un semestre y presupuesto académico (R-04). El escenario más difícil (EC-03) requiere consistencia entre observaciones, reputación y estado de confianza.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| **A. Monolito modular (módulos = contextos DDD, fronteras verificadas por pruebas)** | Una unidad desplegable; transacciones locales; refactor barato; fácil de probar | Escala como un todo; requiere disciplina para no acoplar módulos |
| B. Microservicios + bus de eventos + Kubernetes | Despliegue y escalado independientes; aislamiento fuerte | Costo operacional alto; consistencia distribuida; depuración compleja para 3 personas |
| C. Monolito en capas tradicional | Simple | Sin fronteras de dominio; el módulo comercial se mezclaría con todo (riesgo para EC-05) |
| D. BaaS (Supabase/Firebase) | Muy rápido de construir | Lógica dispersa, difícil de probar con TDD; poca arquitectura propia que evaluar |

Puntajes ponderados en el documento de arquitectura v1, §4.1 (monolito modular = 23, microservicios = 14, BaaS = 3).

## Decisión
Usaremos un **monolito modular**: un módulo por contexto (Catálogo, Establecimientos, Precios, Confianza, Comparación, Moderación, Identidad, Ingesta). Cada módulo expone una interfaz pública, es dueño de su esquema de BD y se comunica con otros mediante eventos de dominio (ADR-0003) o llamadas a su interfaz pública. Internamente, cada módulo sigue arquitectura hexagonal (dominio sin dependencias de infraestructura).

## Consecuencias
- **Positivas:** el módulo comercial será un módulo nuevo que publica eventos; ruta de migración a servicios con Strangler Fig si se necesita.
- **Negativas:** el aislamiento depende de la disciplina; un fallo grave afecta todo el proceso.
- **Verificación:** prueba de arquitectura AR-01 en CI (dependencias prohibidas entre módulos); medida M-07 en el corte 3.
