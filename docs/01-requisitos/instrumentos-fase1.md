# Instrumentos de la Fase 1 — Análisis de actores y benchmarking

> La guía (§8, Fase 1) exige entrevistas breves o encuestas a consumidores y comerciantes, **incluida al menos una tienda de barrio**, y un benchmarking de soluciones existentes. Este documento contiene los instrumentos; **el trabajo de campo lo debe hacer el equipo** y sus resultados alimentan los valores ⚑ de la especificación.

## 1. Consentimiento (leer antes de cada entrevista)

> "Somos estudiantes de Ingeniería de Software de la Universidad Alexander von Humboldt. Estamos diseñando una plataforma para comparar precios entre tiendas y supermercados de Armenia. La entrevista dura unos 10 minutos, es voluntaria y anónima: no registraremos su nombre ni el de su negocio en el informe. ¿Está de acuerdo en participar?"

## 2. Guía de entrevista — Consumidor (8–10 min)

| # | Pregunta | Qué valida |
|---|---|---|
| 1 | ¿Dónde compra normalmente el mercado de la semana o de la quincena? ¿Por qué ahí? | Contexto, tipos de establecimiento |
| 2 | ¿Compara precios antes de comprar? ¿Cómo lo hace hoy? | Necesidad real; competencia actual |
| 3 | Piense en la última vez que encontró un producto más barato en otro lugar. ¿Qué producto y cuánta diferencia había? | Magnitud del problema (dato para el planteamiento) |
| 4 | Si una aplicación le mostrara el precio de un producto en varias tiendas cercanas, ¿qué tendría que ver para confiar en ese precio? | Credibilidad (EC-03), campos de RF-13 |
| 5 | ¿Qué tan viejo puede ser un precio para que todavía le sirva? (mostrar opciones: 1 día, 3 días, 1 semana, 2 semanas) | **Umbral de vigencia RN-04** |
| 6 | ¿Estaría dispuesto a registrar precios que ve en la tienda? ¿Cuánto tiempo le dedicaría por producto? | EC-01 (≤ 15 s), motivación del reportante |
| 7 | ¿Qué le preocuparía de usar una aplicación así? (privacidad, datos, publicidad) | EC-07, neutralidad |
| 8 | ¿Prefiere ver primero el más barato o el más cercano? | Criterio de orden por defecto (RN-10) |

**Datos de clasificación (sin identificar):** rango de edad, barrio/comuna, uso de smartphone (sí/no), tipo de conexión habitual (plan de datos/prepago/wifi).

## 3. Guía de entrevista — Comerciante (tienda de barrio o minimercado) (10 min)

| # | Pregunta | Qué valida |
|---|---|---|
| 1 | ¿Cuántos productos maneja aproximadamente? ¿Tiene sistema de caja (POS) o lista de precios? | Pregunta de diseño 6 |
| 2 | ¿Con qué frecuencia cambian sus precios? ¿Qué productos cambian más seguido? | **Umbral de actualidad EC-02 y vigencia por categoría RN-04** |
| 3 | ¿Cómo se entera de los precios de la competencia? | Riesgo de coordinación de precios (R-03) |
| 4 | Si los clientes publicaran los precios de su tienda en una aplicación, ¿qué le parecería? ¿Qué le preocuparía? | Equidad, corrección de información errónea |
| 5 | ¿Le gustaría publicar sus precios o promociones usted mismo? ¿Cuánto tiempo a la semana le dedicaría? | Módulo comercial (corte 2), pregunta 6 |
| 6 | ¿Qué haría si viera un precio equivocado de su tienda en la aplicación? | Flujo de corrección y moderación |
| 7 | ¿Hace promociones? ¿De qué tipo? (descuento, pague X lleve Y, precio especial) | Reglas de promociones (corte 2) |

## 4. Encuesta corta en línea (consumidores) — 7 preguntas cerradas

1. ¿Con qué frecuencia hace mercado? (diario / semanal / quincenal / mensual)
2. ¿Dónde compra principalmente? (tienda de barrio / minimercado / supermercado / plaza de mercado) — selección múltiple
3. ¿Compara precios antes de comprar? (nunca / a veces / siempre)
4. ¿Cuánto cree que podría ahorrar al mes comparando? (< $20.000 / $20.000–50.000 / > $50.000 / no sé)
5. ¿Hasta cuántos días de antigüedad aceptaría en un precio? (1 / 3 / 7 / 14)
6. ¿Registraría precios si le toma menos de 15 segundos? (sí / tal vez / no)
7. ¿Qué tan importante es para usted que nadie sepa qué tiendas visita? (escala 1–5)

**Meta mínima sugerida:** ≥ 30 respuestas de consumidores, ≥ 3 entrevistas a consumidores y ≥ 3 a comerciantes (≥ 1 tienda de barrio).

## 5. Plantilla de síntesis de hallazgos

| Hallazgo | Evidencia (n, cita) | Impacto en requisitos/escenarios | Acción |
|---|---|---|---|
| p. ej., "Los precios de fruver cambian cada 2–3 días" | 3 de 3 comerciantes | RN-04: vigencia de perecederos = 3 días | Confirmar valor ⚑ |

## 6. Benchmarking

**Criterios de análisis:** origen de los datos, mecanismo de confiabilidad, actualidad, neutralidad/monetización, cobertura de tiendas pequeñas, lección para el proyecto.

Soluciones candidatas a revisar (**el equipo debe verificar cada una en su sitio oficial y citarla en APA**; las descripciones son un punto de partida):

| Solución | Tipo / país | Cómo obtiene los datos | Cómo trata la confiabilidad | Lección para el proyecto |
|---|---|---|---|---|
| Open Prices (Open Food Facts) | Colaborativa, abierta, internacional | Usuarios suben precios con foto de la etiqueta o del recibo | Prueba fotográfica asociada a cada precio | La foto como evidencia (RF-08 opcional) y los datos abiertos |
| GasBuddy | Colaborativa, combustibles, EE. UU./Canadá | Usuarios reportan precios por estación | Marca de tiempo visible, incentivos por puntos | Mostrar la antigüedad y motivar al reportante (RF-12) |
| Precios Claros | Oficial, Argentina | Los comercios grandes informan por obligación normativa | Fuente única oficial | Los precios oficiales no cubren tiendas pequeñas: el modelo colaborativo llena ese vacío |
| Quién es Quién en los Precios (PROFECO) | Oficial, México | Levantamiento por personal de la institución | Recolección controlada | Alta confiabilidad pero baja frecuencia y cobertura |
| SIPSA (DANE) | Oficial, Colombia | Encuestas en centrales mayoristas | Metodología estadística oficial | Útil como referencia mayorista, no como precio de tienda (ADR-0010) |
| Catálogos en línea de cadenas (Éxito, Olímpica, D1, Ara, etc.) | Privados, Colombia | Cada cadena publica los suyos | Fuente única con interés propio | No comparan entre cadenas y excluyen tiendas de barrio; no se hará *scraping* (R-06) |

**Conclusión esperada del benchmarking (a confirmar):** ninguna solución identificada combina (a) datos colaborativos, (b) cobertura de tiendas de barrio y (c) un mecanismo explícito de credibilidad y neutralidad en el contexto del Quindío; ese vacío justifica el proyecto.
