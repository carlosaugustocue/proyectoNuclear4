# ADR-0012: Stack tecnológico

- **Estado:** PENDIENTE — decisión del equipo
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-10 (TDD), EC-05 (pruebas de arquitectura), R-04

## Contexto
La guía deja lenguajes y frameworks a decisión del equipo. Criterios: dominio previo del equipo (pesa más que todo), soporte de tipos decimales, herramientas de TDD y de pruebas de arquitectura, generación/validación OpenAPI y ecosistema para PWA.

## Alternativas consideradas
| Opción | TDD | Pruebas de arquitectura | OpenAPI | Decimales | Comentario |
|---|---|---|---|---|---|
| Java 21 + Spring Boot (Spring Modulith) | JUnit 5 | ArchUnit / Spring Modulith | springdoc | `BigDecimal` | Spring Modulith encaja con el monolito modular y eventos |
| TypeScript + NestJS | Jest/Vitest | dependency-cruiser | @nestjs/swagger | decimal.js | Un solo lenguaje con la PWA |
| C# + ASP.NET Core | xUnit | NetArchTest | Swashbuckle | `decimal` nativo | Muy buen soporte de decimales |
| Python + FastAPI | pytest | import-linter | nativo | `Decimal` | Rápido de escribir; tipado más débil |

PWA: React o Vue + Vite + Workbox (Service Worker) + IndexedDB (idb / Dexie).

## Decisión
*Por definir en reunión de equipo.* Recomendación técnica: elegir el lenguaje que mejor domine el equipo; si hay empate, **Java + Spring Modulith** (soporte nativo de módulos y eventos) o **TypeScript + NestJS** (un solo lenguaje en todo el sistema).

## Consecuencias
Se completa al decidir.
