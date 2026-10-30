# ADR-0006: Cliente como aplicación web progresiva (PWA) con registro offline-first

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-01, EC-09, EC-08

## Contexto
El reportante registra precios frente a la góndola con conectividad limitada (EC-01) y no debe perder registros (EC-09). Las interfaces deben cumplir WCAG 2.2 AA (EC-08). El equipo no tiene capacidad para mantener apps nativas para Android y iOS.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| **PWA (Service Worker + IndexedDB + Background Sync cuando esté disponible)** | Una base de código; instalable; offline; sin tiendas de apps | Background Sync no soportado en todos los navegadores (iOS) → reintento al abrir la app |
| App nativa / multiplataforma (Flutter, React Native) | Mejor acceso a cámara y almacenamiento | Dos *builds*, publicación en tiendas, más esfuerzo |
| Web tradicional sin offline | Simple | Incumple EC-09; EC-01 depende de la red |

## Decisión
Usaremos una **PWA**. Todo registro se guarda primero en una cola local (IndexedDB) con una `Idempotency-Key` (UUID v4) y se confirma al usuario de inmediato; la sincronización reintenta con retroceso exponencial. El formulario de registro se limita a 3 pasos: presentación (búsqueda o código de barras), establecimiento (sugerido por cercanía, con consentimiento) y precio. Opción "sigue igual" de un toque.

## Consecuencias
- **Positivas:** EC-01 no depende de la red; EC-09 garantizado por idempotencia.
- **Negativas:** el usuario puede ver su reporte como "pendiente de envío"; hay que comunicarlo bien.
- **Verificación:** S-09 (modo avión), US-01 (prueba con usuarios), A-01/A-02 (accesibilidad).
