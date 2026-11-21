# ADR-0021: Quality gate con SonarCloud y escaneo de imágenes con Trivy, con umbrales tomados del plan de calidad

- **Estado:** Aceptado · **Fecha:** 2026-11-21
- **Responsable(s):** equipo (calidad: Sofía Giraldo González; CI: Carlos Augusto Aranzazu Jinete)
- **Escenarios relacionados:** EC-12, EC-10, EC-05 · Medidas M-07, M-13, M-14, M-20

## Contexto
B2 exige un quality gate de análisis estático y un escaneo de vulnerabilidades de la imagen del contenedor, cuyos umbrales deben relacionarse con el plan de aseguramiento de calidad. Hoy el CI tiene PHPStan nivel 8, Deptrac y cobertura con pcov (`--min=80`). M-14 menciona SonarQube o SonarCloud, pero no estaba implementado.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Solo PHPStan + Deptrac (lo actual) | Ya funciona | No hay quality gate sobre código nuevo, duplicación, *security hotspots* ni el frontend TypeScript. No cumple el ejemplo del Anexo. |
| SonarQube autoalojado | Control total | Un servidor más que operar (R-04) |
| **SonarCloud (gratuito para repositorios públicos)**, con PHPStan y Deptrac como puertas propias | Quality gate gestionado sobre código nuevo (PHP + TS). Importa la cobertura de pcov. Decorado de PRs. | Dependencia de un servicio externo; requiere crear la organización y el token |
| Escaneo de imagen con Grype o Snyk | Alternativas maduras | Snyk limita su plan gratuito; Grype no escanea secretos |
| **Trivy** (`aquasecurity/trivy-action`) | Libre (Apache-2.0); escanea imágenes, dependencias y secretos | Puede reportar CVE sin corrección: se filtra con `ignore-unfixed` |

## Decisión
| Puerta | Umbral que bloquea | Medida del plan |
|---|---|---|
| Pruebas Pest (unitarias + integración) | Cualquier fallo | EC-10 |
| Cobertura de líneas (pcov) | < 80 % | M-13 |
| PHPStan nivel 8 | ≥ 1 error | M-14 |
| SonarCloud quality gate (código nuevo) | Falla si hay bugs o vulnerabilidades nuevas con calificación peor que A, *security hotspots* sin revisar, cobertura del código nuevo < 80 % o duplicación > 3 % | M-14, M-13 |
| Deptrac (contextos + capas) | ≥ 1 violación | M-07 (AR-01, AR-02) |
| Spectral (OpenAPI) | ≥ 1 error | Contrato API |
| Trivy, imágenes `api` y `web` | ≥ 1 vulnerabilidad CRITICAL con corrección disponible | M-20 |
| Trivy, secretos en el repositorio | ≥ 1 secreto | M-20, R-01 |
| Prueba de humo en staging (k6) | Cualquier error, o p95 > 2 s | EC-04 |

Las licencias se registran en el informe (Ley 23 de 1982, Decisión Andina 351): Trivy usa Apache-2.0; SonarCloud ofrece servicio gratuito para código abierto; k6 usa AGPL-3.0, solo como herramienta.

## Consecuencias
- **Positivas:** cada puerta se traza a una medida del plan; los umbrales del plan se aplican automáticamente.
- **Negativas / riesgos:**
  - Falsos positivos de Trivy en imágenes base: se gestionan con `.trivyignore` justificado, línea por línea.
  - Si SonarCloud no está disponible, las demás puertas siguen bloqueando.

## Verificación
- S-12: un PR con una prueba rota y otro con una dependencia o imagen con una CVE CRITICAL corregible terminan en rojo y no despliegan (M-18 = 100 %).
- Capturas de las ejecuciones en `docs/02-calidad/evidencias/`.
