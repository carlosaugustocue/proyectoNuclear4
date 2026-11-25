# Pruebas de sistema (S-xx) y accesibilidad automática (A-01)

- **Fecha de la medición:** 2026-11-25
- **Suite:** [`e2e/`](../../../../e2e) (Playwright 1.49.1 + @axe-core/playwright 4.10.1), en el job `e2e` del CI
- **Entorno:** [`docker-compose.e2e.yml`](../../../../docker-compose.e2e.yml), con las mismas imágenes que se despliegan en Railway
  (PWA compilada con su CSP y su service worker, API y worker de producción) y una base efímera con la semilla
- **Navegador:** Chromium, perfil móvil Pixel 7, `es-CO`, zona `America/Bogota`

## Resultado

| Prueba | Requisito / escenario | Qué verifica | Resultado |
|---|---|---|---|
| S-08 | RF-08, RN-08, EC-01 | Registro en cuatro pasos con conexión: una sola petición, 202, `Idempotency-Key` UUID y nada pendiente en el teléfono | ✅ |
| S-09 | RF-09, EC-09, **M-12** | 3 precios registrados sin conexión con el service worker activo. Al reconectar se sincronizan solos. Se simula además que el servidor guarda el primer reporte y la respuesta se pierde. Se comprueba en el servidor (`GET /v1/mis-aportes`): exactamente 3 aportes y el reenvío devuelve el mismo `observacionId` | ✅ M-12 = 3/3 = **1**, 0 duplicados |
| S-10 | RF-10, RF-12, RF-19, RN-10 | Sin cuenta invita a entrar. Con cuenta, «Sigue igual» responde 202, «Marcar como dudoso» responde 201, el orden de la comparación no cambia y el aporte aparece en Mis aportes | ✅ |
| S-11 | RF-11, EC-07 | «Distancia» usa la ubicación del dispositivo. Las distancias van en orden ascendente y el criterio queda a la vista | ✅ |
| S-15 | RF-13, RF-15, RN-10, EC-06, EC-07 | Buscar y comparar: el precio por unidad va en orden ascendente, se muestra el criterio declarado por la API, la primera tarjeta es la más barata y la respuesta no trae datos del reportante | ✅ |
| A-01 | EC-08, **M-11** | axe con las reglas WCAG 2.0, 2.1 y 2.2 de nivel A y AA en 17 pantallas, incluidos los cuatro pasos del registro y el estado de error | ✅ M-11 = **0 violaciones** |

## Hallazgos que la suite encontró y se corrigieron (ciclo rojo → verde)

| Id | Hallazgo | Corrección | Commits |
|---|---|---|---|
| H-E2E-01 | **A-01:** 12 violaciones en 11 pantallas ([antes](axe-2026-11-25-antes.md)). La mayoría era `color-contrast`: las etiquetas de la barra de navegación, los pies de foto y algunos textos auxiliares estaban en `slate-400`, con contraste 2,63:1 frente a un mínimo de 4,5:1. Además, en el registro había elementos `<li>` dentro de un `radiogroup`. La barra de navegación tenía ese color desde el prototipo del 2026-11-11. | Texto en `slate-500` (4,76:1), o `slate-600` sobre el fondo de una opción seleccionada. Los radiogroup pasan a `<div>`. La etiqueta «Navegación principal» queda con tilde. Resultado: [después](axe-2026-11-25-despues.md), con 0 violaciones | `8c2f55b` (rojo) → `c938e8a` (verde) |
| H-E2E-02 | **S-09:** si el servidor guardaba el reporte pero la respuesta se perdía, la cola cortaba la ronda y no volvía a intentar mientras hubiera red. Los 3 reportes quedaban pendientes hasta que la conexión se cayera y volviera. Es un caso frecuente en 3G y viola EC-09. | El store programa reintentos con espera exponencial (2 s, 4 s, 8 s… hasta 60 s) mientras haya red y queden pendientes. Siempre reenvía con la misma `Idempotency-Key`, así que no hay duplicados. Prueba unitaria: `frontend/src/stores/reportes.test.ts` | `583e5be` (rojo) → `3da3057` (verde) |

## Alcance y límites

- axe detecta automáticamente una parte de los criterios WCAG. La revisión manual con teclado y lector de pantalla (A-02) sigue siendo responsabilidad del equipo.
- Las pruebas usan la semilla de demostración, que son datos ficticios, y correos sintéticos `@ejemplo.test`.
- S-09 simula el modo avión con la emulación de red de Chromium (`context.setOffline`). No reemplaza una prueba en un teléfono real con red 3G (US-01).

## Cómo reproducir

```bash
docker compose -p pn4-e2e -f docker-compose.e2e.yml up -d --build --wait
docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD/e2e":/e2e -w /e2e \
  mcr.microsoft.com/playwright:v1.49.1-noble sh -c "npm ci && npx playwright test"
# Informe: e2e/resultados/informe/index.html · reporte axe: e2e/resultados/axe/resumen.md
docker compose -p pn4-e2e -f docker-compose.e2e.yml down -v
```
