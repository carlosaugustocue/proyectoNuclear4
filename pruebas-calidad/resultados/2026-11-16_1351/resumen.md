# Resultados de las mediciones de calidad

- Fecha: 2026-11-16T13:51:24-05:00 · commit `b4d4e5a`
- Equipo: Intel(R) Core(TM) i7-6500U CPU @ 2.50GHz (4 hilos), 11.6 GB de RAM, Docker 29.8.0
- API: imagen de producción (`docker/railway/api.Dockerfile`), `php artisan serve` con PHP_CLI_SERVER_WORKERS=1; PostgreSQL 16 + PostGIS en el mismo equipo

> Mediciones en un equipo local, no en Railway: describen el comportamiento de la arquitectura y de la configuración de producción, pero los tiempos absolutos cambiarán con el hardware del proveedor. Los datos son sintéticos (prefijos «[CARGA]» y «[EC-03]»).

## EC-03 · Detección de anomalías (M-03, M-04)

Conjunto **sintético etiquetado** de 250 reportes por semilla (50 anómalos, 20 %), procesado por el caso de uso real. Detectado = estado `POR_VERIFICAR`.

| Semilla | M-03 exhaustividad | M-04 falsos positivos | Sutiles marcados | Descuentos legítimos marcados | Anómalos con caso de moderación |
|---|---|---|---|---|---|
| 1 | 0.980 | 0.035 | 5/6 | 4/15 | 49/50 |
| 2 | 0.960 | 0.075 | 4/6 | 10/15 | 48/50 |
| 3 | 0.940 | 0.030 | 3/6 | 5/15 | 47/50 |
| 4 | 0.860 | 0.015 | 2/6 | 2/15 | 43/50 |
| 5 | 0.880 | 0.005 | 1/6 | 1/15 | 44/50 |

| Medida | Media | Peor caso | Umbral aceptable | Resultado (peor caso) |
|---|---|---|---|---|
| M-03 | 0.924 | 0.860 | ≥ 0,80 | 🟡 aceptable |
| M-04 | 0.032 | 0.075 | ≤ 0,05 | ❌ no aceptable |

## EC-02 · Latencia de actualidad (M-02)

Tiempo desde `POST /reportes-precio` hasta que `GET /comparaciones` muestra el precio nuevo, por el flujo asíncrono real (outbox → worker → Confianza → proyección). Resolución ~200 ms.

| p50 | p95 | Máximo | Cambios sin reflejar | Umbral aceptable (todos) | Resultado |
|---|---|---|---|---|---|
| 1.02 s | 1.35 s | 2.79 s | 0 | ≤ 5 min (objetivo ≤ 1 min) | ✅ objetivo |

## EC-04 · Carga de consultas (M-05, M-06)

500 usuarios virtuales concurrentes (rampa 1m, sostenida 5m), pausa de 1–3 s entre consultas; 2.000 presentaciones y 300 establecimientos. Peticiones: 11902 (30.7/s).

| Medida | p50 | p95 | p99 | Máximo | Umbral aceptable | Resultado |
|---|---|---|---|---|---|---|
| M-05 tiempo de respuesta (fase sostenida) | 5.36 s | 26.56 s | 52.48 s | 58.99 s | p95 ≤ 2 s | ❌ no aceptable |
| M-06 tasa de error | | | | | < 1 % | 20.99 % · ❌ no aceptable |
