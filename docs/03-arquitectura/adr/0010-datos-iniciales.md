# ADR-0010: Datos iniciales — carga semilla de campo y SIPSA como referencia mayorista separada

- **Estado:** Propuesto
- **Fecha:** 2026-10-30
- **Escenarios relacionados:** EC-03 (arranque en frío), compatibilidad, pregunta de diseño 5, R-06

## Contexto
Sin comunidad de reportantes no hay datos, y sin datos nadie usa la plataforma. La guía sugiere el SIPSA del DANE. El SIPSA publica **precios mayoristas** de productos agroalimentarios en centrales de abasto, no precios de cada tienda: mezclarlos con precios minoristas engañaría al consumidor.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| *Scraping* de catálogos de grandes cadenas | Muchos datos | Riesgo frente a términos de uso (R-06); sesga hacia grandes superficies (equidad) |
| SIPSA mezclado con precios de tienda | Datos oficiales | Son mayoristas: comparación incorrecta |
| **Carga semilla de campo + SIPSA como "referencia mayorista" separada** | Datos reales locales; transparencia | Esfuerzo de campo del equipo |

## Decisión
1. El equipo hará recorridos de campo en Armenia (≥ 10 establecimientos, incluidas tiendas de barrio, y ≥ 100 presentaciones) y los cargará con RF-26, con la fuente "equipo del proyecto".
2. El SIPSA se importará como **referencia mayorista**, mostrada aparte de la comparación (p. ej., "Precio mayorista de referencia en Armenia: $X/kg"). *Por verificar:* formato, frecuencia y condiciones de uso de los datos del SIPSA.
3. No se hará *scraping* de sitios de terceros sin autorización.

## Consecuencias
- **Positivas:** datos reales para la demostración y para el conjunto de prueba de EC-03; respeta R-06.
- **Negativas:** esfuerzo de campo; se puede combinar con las entrevistas de la Fase 1.
- **Verificación:** I-25, I-26; recuento de datos semilla cargados.
