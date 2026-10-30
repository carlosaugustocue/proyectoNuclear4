# ADR-0005: Persistencia en PostgreSQL con PostGIS

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-03, EC-04, EC-02, RF-06

## Contexto
Los datos son relacionales (productos, presentaciones, establecimientos, observaciones), requieren transacciones (outbox, ADR-0003), consultas geoespaciales (cercanía, RF-06), estadística por grupo (mediana/MAD, RN-06) y aritmética decimal exacta (RN-02).

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| **PostgreSQL + PostGIS** | Transacciones, `numeric`, índices espaciales GiST, `percentile_cont`, esquemas por módulo, gratuito | Escalado de escritura vertical (suficiente aquí) |
| MySQL | Conocido | Soporte geoespacial y estadístico más limitado |
| MongoDB | Esquema flexible | Transacciones y *joins* menos naturales para este dominio |
| PostgreSQL + Redis como caché | Menor latencia | Pieza adicional; se agregará **solo si** K-01 muestra p95 > 1,5 s |

## Decisión
Usaremos **PostgreSQL 16 con PostGIS**, un esquema por módulo, migraciones versionadas en el repositorio y tipo `numeric` para dinero y cantidades. La caché externa queda diferida hasta que una medición la justifique.

## Consecuencias
- **Positivas:** un solo motor para todo; pruebas de integración realistas con Testcontainers.
- **Negativas:** la BD es punto único de falla (aceptable por criticidad media).
- **Verificación:** K-01 (EC-04) e I-06 (consultas por radio).
