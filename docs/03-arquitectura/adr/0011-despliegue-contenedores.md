# ADR-0011: Despliegue reproducible con contenedores Docker y Compose; CI en GitHub Actions

- **Estado:** Propuesto (el proveedor de nube se decide en el corte 2, Fase 3)
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** mínimo exigible "despliegue reproducible", EC-10, RDA3 de AS II

## Contexto
La guía exige despliegue reproducible y deja al equipo la elección de proveedor, modelo de servicio y orquestación. El presupuesto es académico/gratuito (R-04).

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| **Docker + Docker Compose; IaaS (VM) o PaaS de contenedores** | Reproducible en local y en nube; simple | Escalado manual |
| Kubernetes (k3s / gestionado) | Cubre "orquestación de contenedores" del RDA3 | Complejidad alta para el tamaño del sistema |
| PaaS sin contenedores | Muy simple | Menor control; reproducibilidad depende del proveedor |

## Decisión
Empaquetaremos API, *worker* y BD como contenedores y los levantaremos con `docker compose`. CI en GitHub Actions: pruebas, cobertura, análisis estático, prueba de arquitectura y construcción de imágenes. *A decidir en el corte 2:* proveedor (p. ej., VM con créditos educativos de Azure/AWS/GCP, o un PaaS de contenedores). Se evaluará un manifiesto mínimo de Kubernetes (k3s) solo como demostración del RDA3 si el docente lo exige.

## Consecuencias
- **Positivas:** cualquier integrante y el docente pueden levantar el sistema con un comando.
- **Negativas:** sin autoescalado; aceptable por la carga esperada.
- **Verificación:** `docker compose up` en máquina limpia; pipeline en verde.
