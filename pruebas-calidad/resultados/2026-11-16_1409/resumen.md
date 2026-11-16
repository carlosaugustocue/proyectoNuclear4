# Resultados de las mediciones de calidad

- Fecha: 2026-11-16T14:10:04-05:00 · commit `b4d4e5a`
- Equipo: Intel(R) Core(TM) i7-6500U CPU @ 2.50GHz (4 hilos), 11.6 GB de RAM, Docker 29.8.0
- API: imagen de producción (`docker/railway/api.Dockerfile`), `php artisan serve` con PHP_CLI_SERVER_WORKERS=4; PostgreSQL 16 + PostGIS en el mismo equipo

> Mediciones en un equipo local, no en Railway: describen el comportamiento de la arquitectura y de la configuración de producción, pero los tiempos absolutos cambiarán con el hardware del proveedor. Los datos son sintéticos (prefijos «[CARGA]» y «[EC-03]»).

## EC-04 · Carga de consultas (M-05, M-06)

500 usuarios virtuales concurrentes (rampa 1m, sostenida 5m), pausa de 1–3 s entre consultas; 2.000 presentaciones y 300 establecimientos. Peticiones: 21328 (55.4/s).

| Medida | p50 | p95 | p99 | Máximo | Umbral aceptable | Resultado |
|---|---|---|---|---|---|---|
| M-05 tiempo de respuesta (fase sostenida) | 2.67 s | 13.50 s | 26.82 s | 58.99 s | p95 ≤ 2 s | ❌ no aceptable |
| M-06 tasa de error | | | | | < 1 % | 6.21 % · ❌ no aceptable |
