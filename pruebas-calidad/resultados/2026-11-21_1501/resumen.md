# Resultados de las mediciones de calidad

- Fecha: 2026-11-21T15:01:43-05:00 · commit `7a743f6`
- Equipo: Intel(R) Core(TM) i7-6500U CPU @ 2.50GHz (4 hilos), 11.6 GB de RAM, Docker 29.8.0
- API: imagen de producción (`docker/railway/api.Dockerfile`), `php artisan serve` con PHP_CLI_SERVER_WORKERS=4; PostgreSQL 16 + PostGIS en el mismo equipo

> Mediciones en un equipo local, no en Railway: describen el comportamiento de la arquitectura y de la configuración de producción, pero los tiempos absolutos cambiarán con el hardware del proveedor. Los datos son sintéticos (prefijos «[CARGA]» y «[EC-03]»).

## EC-11 · Carga de la canasta comparada (M-16, M-17)

200 usuarios virtuales concurrentes (rampa 1m, sostenida 3m), canastas de 20 productos al azar, pausa de 2–5 s; 300 establecimientos. Peticiones: 3436 (12.9/s).

| Medida | p50 | p95 | p99 | Máximo | Umbral aceptable | Resultado |
|---|---|---|---|---|---|---|
| M-16 tiempo de respuesta (fase sostenida) | 10.38 s | 11.02 s | 11.98 s | 27.82 s | p95 ≤ 3 s | ❌ no aceptable |
| Tasa de error | | | | | < 1 % | 0.06 % · ✅ objetivo |
| M-17 faltantes señalados y no sumados | | | | | = 1 | 3434/3434 respuestas · ✅ objetivo |
