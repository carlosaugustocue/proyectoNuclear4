# Cómo trabajamos

La guía del proyecto verifica la contribución individual con el historial del repositorio (§10). Por eso **todo cambio entra por *pull request*** y cada integrante trabaja con su propia cuenta.

## Flujo

1. Actualiza `main`: `git checkout main && git pull`.
2. Crea una rama corta con un prefijo:
   - `docs/…` documentación
   - `feat/…` funcionalidad
   - `fix/…` corrección
   - `test/…` pruebas
   - `chore/…` configuración
3. Haz commits pequeños con [Conventional Commits](https://www.conventionalcommits.org/es/v1.0.0/):
   ```
   docs(arquitectura): agregar ADR-0013 sobre caché de comparaciones
   test(confianza): caso límite de RN-06 con MAD = 0
   feat(confianza): calcular puntaje z modificado
   ```
   Alcances sugeridos: `requisitos`, `calidad`, `arquitectura`, `api`, y el nombre de cada módulo (`catalogo`, `precios`, `confianza`, `comparacion`…).
4. Abre un *pull request* hacia `main` usando la plantilla. Lo revisa **al menos otro integrante** antes de fusionarlo.

## TDD (desde el corte 2)

En las reglas de negocio (RN-01 a RN-10) el historial debe mostrar el ciclo completo, porque es la evidencia del informe de TDD:

| Paso | Commit |
|---|---|
| Rojo: prueba que falla | `test(modulo): …` |
| Verde: código mínimo | `feat(modulo): …` |
| Refactor | `refactor(modulo): …` |

## Documentación

- Toda decisión estructural nueva necesita un ADR en `docs/03-arquitectura/adr/` (copia `0000-plantilla.md`). Un ADR aceptado no se edita: se reemplaza con uno nuevo.
- Si cambia un requisito, un escenario o una prueba, actualiza la [matriz de trazabilidad](docs/01-requisitos/matriz-trazabilidad.md).
- Si cambia la API, actualiza `docs/03-arquitectura/api/openapi.yaml` en el mismo PR.
