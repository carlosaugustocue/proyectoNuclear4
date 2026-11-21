# ADR-0020: Entrega continua con GitHub Actions hacia los entornos staging y production de Railway, con promoción manual

- **Estado:** Aceptado · **Fecha:** 2026-11-21 · Complementa ADR-0011 y ADR-0013
- **Responsable(s):** equipo (pruebas y CI: Carlos Augusto Aranzazu Jinete)
- **Escenarios relacionados:** EC-12 (Anexo 1), EC-10, EC-04

## Contexto
La opción B2 exige:
- Un pipeline como código hasta staging, con prueba de humo.
- Promoción manual a producción.
- Un procedimiento de reversión.
- Secretos fuera del repositorio.

EC-12 pide bloquear el 100 % de los cambios defectuosos y que un cambio válido llegue a staging en ≤ 15 min sin pasos manuales. ADR-0013 ya eligió Railway, y las imágenes de `docker/railway/` están probadas. Railway ofrece **entornos** aislados dentro de un proyecto, *project tokens* limitados a un entorno, `railway up --ci` para desplegar desde CI y *rollback* de un despliegue anterior sin reconstruir.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Autodespliegue de Railway desde GitHub (cada push a `main`) | Configuración mínima | El pipeline no controla el orden: Railway puede desplegar aunque falle una etapa de calidad, y la prueba de humo queda fuera del flujo |
| Construir la imagen en Actions, publicarla en GHCR y apuntar Railway a la etiqueta | Se despliega exactamente el artefacto escaneado | Railway no redespliega solo con una etiqueta nueva y hay reportes de que no vuelve a descargar etiquetas mutables. Habría que cambiar la referencia de imagen por la API GraphQL: más complejidad y más fragilidad. |
| Kubernetes gestionado (AKS/GKE) con canary | Máxima capacidad de orquestación | El costo y la operación son inviables para el equipo (R-04); ADR-0002 lo descartó |
| **GitHub Actions orquesta: CI + quality gate + Trivy → `railway up --ci` a staging → humo k6 → aprobación manual → `railway up --ci` a production** | El pipeline decide qué se despliega y en qué orden. Usa los entornos y el *rollback* nativos de Railway. Tokens limitados por entorno. | Railway reconstruye la imagen desde el mismo commit y Dockerfile: no es el mismo binario que escanea Trivy. La creación inicial de los entornos se hace una vez en el panel (sin IaC declarativa). |

## Decisión
- `.github/workflows/cd.yml` corre en cada push a `main`. Encadena:
  - El CI reutilizable (pruebas, cobertura, PHPStan, Deptrac, Spectral, build).
  - El quality gate y el escaneo (ADR-0021).
  - El job `staging`: `railway up --ci --service <api|worker|web> --environment staging` y luego la prueba de humo k6.
  - El job `produccion`, en el *GitHub Environment* `production` con revisor requerido, que despliega el mismo commit.
- Los secretos `RAILWAY_TOKEN` (un *project token* por entorno) y `SONAR_TOKEN` viven en GitHub Environments. Las variables de la aplicación viven en Railway.
- La reversión sigue `docs/03-arquitectura/ampliacion-corte3/runbook-reversion.md`.
- **Alternativa de contingencia por costo:** si el crédito de Railway no alcanza para dos entornos, staging se ejecuta en el servidor del equipo con el mismo `docker compose` y el mismo job. Se documentará si se activa.

## Consecuencias
- **Positivas:** ningún cambio llega a staging sin pasar todas las puertas. La promoción queda auditada en GitHub. La reversión toma segundos con *rollback*.
- **Negativas / riesgos:**
  - Artefacto reconstruido (mitigado con el mismo Dockerfile, el mismo commit e imágenes base fijadas).
  - Dependencia de Railway y de la retención de imágenes (72 h en Hobby).
  - El costo de duplicar los servicios.

## Verificación
- S-12: un PR que rompe una prueba y otro con una vulnerabilidad crítica quedan bloqueados (100 %; M-18).
- S-13: tiempo del push al final de la prueba de humo en staging ≤ 15 min (M-19), según las marcas de tiempo de GitHub Actions.
- S-14: ensayo de reversión cronometrado ≤ 5 min (M-21).
