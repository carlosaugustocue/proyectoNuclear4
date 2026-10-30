# ADR-0008: Identidad de producto — producto canónico + presentación + EAN + alias; normalización a unidad base

- **Estado:** Aceptado
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-06 (corrección), consistencia (25012), pregunta de diseño 4

## Contexto
Un mismo producto aparece con nombres distintos ("Arroz Diana 500g", "arroz diana x 500") y en presentaciones distintas (500 g, 1 kg, paca). Sin identidad consistente, la comparación es incorrecta.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| Texto libre + coincidencia difusa | Sin fricción al registrar | Duplicados y comparaciones erróneas |
| Solo código EAN | Identidad exacta | Productos a granel y de tienda sin código |
| **Catálogo canónico moderado + EAN cuando existe + alias** | Consistente; admite granel | Requiere moderación del catálogo |

## Decisión
Modelaremos `Producto` (canónico) → `Presentacion` (cantidad, unidad, unidades por empaque, EAN opcional) → `Alias`. La búsqueda usa nombre, alias y EAN (con el escáner de la cámara cuando esté disponible). Los reportantes pueden proponer productos nuevos (pendientes de moderación). La comparación por unidad usa RN-01 a RN-03 con tipos decimales.

## Consecuencias
- **Positivas:** comparación correcta entre presentaciones; reglas aptas para TDD.
- **Negativas:** trabajo de moderación del catálogo; se mitiga con carga semilla.
- **Verificación:** U-01 a U-03; medida de duplicados ≤ 2 %.
