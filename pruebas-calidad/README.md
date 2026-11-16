# Mediciones automatizadas de calidad

Script reproducible que mide tres escenarios de calidad en local y deja los resultados en
`resultados/<fecha>/resumen.md`. Sirve como evidencia para los informes de calidad (Cal. E5) y de
evaluación de arquitectura (Arq. E6) y para la sustentación.

```bash
cd pruebas-calidad
./ejecutar.sh                                      # 500 usuarios, 3 min sostenidos, 5 semillas, 20 cambios
DURACION=15m ./ejecutar.sh                         # EC-04 completo (15 minutos sostenidos)
SOLO="k01" PHP_CLI_SERVER_WORKERS=4 ./ejecutar.sh  # solo la carga, con 4 procesos PHP
```

Requisitos: Docker, Python 3 y unos 3 GB de RAM libres. No necesita k6 instalado (usa la imagen `grafana/k6`).

## Qué mide

| Escenario | Medidas | Cómo | Archivo |
|---|---|---|---|
| **EC-03** credibilidad | M-03 exhaustividad (≥ 0,80) · M-04 falsos positivos (≤ 0,05) | Conjunto **sintético etiquetado** de 250 reportes por semilla (20 % anómalos: burdos, moderados, sutiles y un ataque coordinado; incluye descuentos legítimos) que pasa por el caso de uso real. Se compara el estado asignado por Confianza con la etiqueta. | `backend/app/Console/Commands/EvaluarDeteccionAnomalias.php` (`php artisan calidad:deteccion`) |
| **EC-02** actualidad | M-02 latencia (≤ 5 min; objetivo ≤ 1 min) | Un usuario cambia 20 veces un precio por `POST /v1/reportes-precio` y consulta cada 200 ms hasta que `GET /v1/comparaciones` lo muestra. Recorre el flujo asíncrono real: outbox → worker → Confianza → proyección. | `k6/i02-actualidad.js` |
| **EC-04** desempeño | M-05 p95 (≤ 2 s) · M-06 errores (< 1 %) | VUS usuarios concurrentes con 1–3 s de pausa entre consultas (70 % por precio, 15 % por distancia, 15 % resumen) sobre 2.000 presentaciones y 300 establecimientos. M-05 se toma solo en la fase sostenida. | `k6/k01-carga-comparacion.js` · `backend/database/seeders/VolumenCargaSeeder.php` |

## Cómo funciona

1. Levanta un entorno **aislado** (`docker-compose.calidad.yml`, proyecto `nuclear-calidad`) con las **mismas
   imágenes que se despliegan en Railway** (`docker/railway/api.Dockerfile`): API con `php artisan serve`,
   worker con `queue:work` y PostgreSQL 16 + PostGIS. No usa ni modifica el entorno de desarrollo.
2. Corre EC-03 con varias semillas, carga la semilla de demostración y el volumen de EC-04, mide EC-02 y
   luego EC-04.
3. Guarda `entorno.json` (equipo, commit, parámetros), los JSON de cada medición y `resumen.md`.
4. Apaga el entorno y borra su base de datos (`CONSERVAR=1` para dejarlo encendido y revisarlo).

## Cómo leer los resultados (limitaciones)

- **No es Railway.** Es un equipo local con la configuración de producción. Los tiempos absolutos cambian con el
  hardware del proveedor; lo que sí muestra es cómo responde la arquitectura y dónde se satura.
- **Los datos son sintéticos** (prefijos «[CARGA]» y «[EC-03]»). El conjunto de EC-03 lo generó el equipo con
  una distribución documentada en el comando; no sustituye un conjunto con reportes reales etiquetados.
- **EC-03 depende de la distribución elegida.** Por eso se corre con varias semillas y se reporta el peor caso.
  Los grupos «sutil» y «descuento legítimo» se solapan a propósito para mostrar la frontera del detector.
- **EC-02 tiene resolución de ~200 ms** (intervalo de consulta). El worker revisa la cola cada 3 s por defecto.
- **EC-01 (usabilidad) y EC-08 (accesibilidad) no se automatizan aquí:** requieren la prueba con usuarios y axe.

## Resultados de la primera ejecución (2026-11-16)

| Escenario | Resultado | Umbral | Veredicto |
|---|---|---|---|
| EC-02 · M-02 | p50 1,02 s · p95 1,35 s · máx 2,79 s | ≤ 5 min (objetivo ≤ 1 min) | ✅ objetivo |
| EC-03 · M-03 | media 0,924 · peor 0,86 (5 semillas) | ≥ 0,80 | 🟡 aceptable |
| EC-03 · M-04 | media 0,032 · peor 0,075 | ≤ 0,05 | ❌ en 1 de 5 semillas (descuentos legítimos marcados) |
| EC-04 · 1 proceso PHP | p95 26,6 s · errores 21,0 % · 30,7 pet/s | p95 ≤ 2 s · < 1 % | ❌ |
| EC-04 · 4 procesos PHP | p95 13,5 s · errores 6,2 % · 55,4 pet/s | p95 ≤ 2 s · < 1 % | ❌ (mejora ×1,8) |

Evidencia: `resultados/2026-11-16_1351/` (completa, 1 proceso) y `resultados/2026-11-16_1409/` (solo carga, 4 procesos).

**Defectos que encontró la herramienta y el CI no veía (corregidos en el mismo PR):**

1. `brick/money` estaba en `require-dev` aunque lo usa el dominio (`Precio`): la imagen de producción, instalada con
   `--no-dev`, fallaba con error 500 al registrar o comparar cualquier precio.
2. `PHP_CLI_SERVER_WORKERS` no tenía efecto: Laravel la ignora si `artisan serve` no recibe `--no-reload`. Se agregó
   la opción al comando de `docker/railway/api.Dockerfile`.

**Conclusión de EC-04:** el modelo de lectura responde rápido (p95 162 ms con 20 usuarios); el límite es el servidor
de aplicaciones (`artisan serve`) y, con 4 procesos, la CPU compartida del portátil. Siguiente paso: un servidor de
producción (php-fpm + nginx, o FrankenPHP/Octane) y repetir K-01 con el generador de carga en otra máquina.

---
Elaborado con apoyo de IA generativa (Claude); el equipo revisa y responde por el diseño de las mediciones.
