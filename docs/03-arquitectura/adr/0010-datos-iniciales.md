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

## Implementación (2026-11-25, rama `feat/catalogo-sipsa`)

Se implementó el punto 2 y se verificó el «por verificar». El punto 1 (recorridos de campo) sigue pendiente; por eso el estado sigue en **Propuesto** hasta que el equipo decida.

- **Formato verificado:** el anexo del DANE (`anex-SIPSA-SerieHistoricaMayorista-2026.xlsx`) trae el promedio **mensual** por producto, mercado y municipio (enero a octubre de 2026). En Armenia hay dos mercados (Mercar y Retiro) y **115 productos** en 8 grupos. La nota al pie aclara que los huevos y el bocadillo van en $/unidad, y el aceite, el vinagre y el jugo de frutas en $/litro; el resto en $/kg.
- **Capa anticorrupción** (`Ingesta/Domain/Services/TraductorSipsa`, prueba U-11): traduce grupo → categoría y vigencia (RN-04) y aplica la unidad de la nota al pie. Las filas que no puede traducir se omiten con su motivo.
- **Catálogo:** `php artisan ingesta:sipsa` crea los productos **genéricos** (sin marca) con una presentación de 1 unidad base, o reutiliza el genérico que ya existe con el mismo nombre (sin tildes, mayúsculas ni espacios sobrantes) para no duplicar el catálogo (D-04). Es idempotente.
- **Referencia separada:** los precios se guardan en `referencias_mayoristas` (contexto Ingesta) y se publican en `GET /v1/productos/{id}/referencia-mayorista`. No crean observaciones ni filas en `comparacion_vigente` (prueba de integración). La PWA los muestra debajo de la comparación, rotulados «No entra en la comparación de tiendas».
- **Desviación del contrato preliminar:** el contrato planificaba `GET /referencias-mayoristas?productoId=`. Se implementó como subrecurso del producto porque así responde 404 cuando el producto no existe y queda junto al catálogo; se actualizó `openapi.yaml` (0.7.0).
- **Datos de demostración:** `PreciosDemostracionSipsaSeeder` siembra precios de góndola **supuestos** (mayorista × 1,35 × factor de la tienda) para que la demo tenga comparaciones con el catálogo real. Son datos de demostración (`EQUIPO_SEMILLA`), no observaciones; reemplazarlos por los del recorrido de campo es el punto 1.
- **Condiciones de uso:** datos públicos del DANE; se cita la fuente en la API, en la PWA y en este registro.

**Referencia:** Departamento Administrativo Nacional de Estadística [DANE]. (2026). *Sistema de Información de Precios y Abastecimiento del Sector Agropecuario (SIPSA): serie histórica de precios mayoristas 2026* [Conjunto de datos]. https://www.dane.gov.co/index.php/estadisticas-por-tema/agropecuario/sistema-de-informacion-de-precios-sipsa
