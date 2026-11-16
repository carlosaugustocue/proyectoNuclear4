# Protocolo y resultados de la prueba de usabilidad — Corte 2

> **Entregable 4 de Modelos de Calidad (Corte 2)** · Fecha: 2026-11-11.
> Evalúa la capacidad de interacción (ISO/IEC 25010:2023) del prototipo PWA sobre los flujos críticos: consulta de precios y registro de precios.

---

## 1. Objetivo

Verificar que el prototipo de la PWA MercadoNorte cumple con los escenarios de calidad EC-01 (registro en <= 15 s) y EC-08 (accesibilidad WCAG 2.2 AA) mediante una prueba con usuarios representativos.

## 2. Escenarios de calidad evaluados

| EC | Atributo ISO 25010 | Medida | Meta |
|---|---|---|---|
| EC-01 | Capacidad de interacción — operabilidad | >= 80% de los intentos en <= 15 s | >= 8 usuarios |
| EC-08 | Capacidad de interacción — inclusividad | 0 violaciones A/AA en axe; checklist WCAG 2.2 AA | Flujos de consulta y registro |
| EC-06 | Adecuación funcional — corrección | Criterio de orden siempre visible; promoción distinguida | 100% de los casos |

## 3. Participantes

| # | Perfil | Edad | Experiencia tecnologica | Dispositivo |
|---|---|---|---|---|
| 1 | Consumidor — ama de casa | 45 | Baja (WhatsApp, Facebook) | Móvil Android |
| 2 | Consumidor — estudiante universitario | 22 | Alta | Móvil Android |
| 3 | Consumidor — adulto mayor | 63 | Baja | Móvil Android |
| 4 | Reportante — estudiante de ingeniería | 24 | Alta | Móvil Android |
| 5 | Reportante — ama de casa | 38 | Media | Móvil Android |
| 6 | Comerciante — tienda de barrio | 52 | Baja–Media | Móvil Android |
| 7 | Consumidor — trabajador independiente | 35 | Media | Móvil iPhone |
| 8 | Consumidor — estudiante de colegio | 17 | Alta | Móvil Android |

> **Nota:** Los participantes son personas reales del entorno del equipo en Armenia, Quindío. Se les explico el propósito de la prueba y se obtuvo consentimiento verbal conforme a la Ley 1581/2012. No se registraron datos personales mas allá del perfil anonimizado.

## 4. Entorno de prueba

| Aspecto | Detalle |
|---|---|
| Prototipo | PWA MercadoNorte v0.1 (PR #7) corriendo en `http://localhost:5173` vía tunel local |
| Datos | Datos mock realistas: 8 productos, 6 establecimientos, 5 precios de comparación |
| Red | Wi-Fi domestico (simulando condiciones normales) |
| Facilitador | Un integrante del equipo guía la sesion sin intervenir en las tareas |
| Observador | Otro integrante registra tiempos y notas |
| Duracion por sesion | 10–15 minutos |

## 5. Tareas asignadas

### Tarea 1 — Consultar precio (flujo del consumidor)

> "Quieres saber donde esta mas barato el arroz en Armenia. Usa la aplicación para encontrar el precio mas bajo."

**Criterio de exito:** El participante llega a la vista de comparación del Arroz Diana y puede identificar cual establecimiento tiene el precio mas bajo.

**Pasos esperados:** Inicio → buscar "arroz" (o tap en producto destacado) → ver comparación → identificar D1 como el mas barato ($4.700/kg).

### Tarea 2 — Registrar un precio (flujo del reportante)

> "Estas en la tienda D1 y ves que el arroz cuesta $2.350. Registra ese precio en la aplicación."

**Criterio de exito:** El participante completa el flujo de 5 pasos y ve la pantalla de confirmación "Precio registrado".

**Tiempo objetivo:** <= 15 segundos (EC-01).

### Tarea 3 — Interpretar estado de un precio

> "En los resultados de comparación, hay un precio que dice 'Puede estar desactualizado'. Qué significa eso para ti?"

**Criterio de exito:** El participante entiende que el precio podría haber cambiado y que debe verificar en la tienda.

### Tarea 4 — Cambiar criterio de orden

> "Ahora quieres ver cual tienda esta mas cerca de ti. Cambia el orden de los resultados."

**Criterio de exito:** El participante encuentra y usa el boton "Distancia" en la barra de orden.

## 6. Resultados

### 6.1 Tarea 1 — Consultar precio

| # | Completo | Tiempo | Camino seguido | Observaciones |
|---|---|---|---|---|
| 1 | Si | 18 s | Inicio → tap producto → comparación | Dudo con el buscador, pero encontró el producto en destacados |
| 2 | Si | 8 s | Inicio → buscador → comparación | Flujo directo |
| 3 | Si | 25 s | Inicio → tap producto → comparación | Necesito orientacion para entender las tarjetas de producto |
| 4 | Si | 6 s | Inicio → buscador → comparación | Flujo directo |
| 5 | Si | 12 s | Inicio → tap producto → comparación | Uso la sección de destacados |
| 6 | Si | 15 s | Inicio → tab Buscar → comparación | Uso el bottom tab bar |
| 7 | Si | 10 s | Inicio → buscador → comparación | Flujo directo |
| 8 | Si | 7 s | Inicio → buscador → comparación | Flujo directo |

**Tasa de exito:** 8/8 (100%)
**Tiempo promedio:** 12.6 s
**Observación clave:** Los usuarios con poca experiencia prefirieron tocar el producto destacado en lugar de usar el buscador. El participante 3 (adulto mayor) necesito mas tiempo pero completo la tarea sin ayuda.

### 6.2 Tarea 2 — Registrar precio

| # | Completo | Tiempo | Pasos necesarios | Observaciones |
|---|---|---|---|---|
| 1 | Si | 22 s | 5 | Tardo en encontrar el boton "Registrar" en el bottom tab |
| 2 | Si | 11 s | 5 | Flujo directo |
| 3 | Si | 35 s | 5 | Necesito tiempo para leer cada paso; completo sin ayuda |
| 4 | Si | 9 s | 5 | Flujo directo |
| 5 | Si | 14 s | 5 | Flujo directo |
| 6 | Si | 18 s | 5 | Dudo en el campo de precio (no sabia si poner punto o no) |
| 7 | Si | 12 s | 5 | Flujo directo |
| 8 | Si | 10 s | 5 | Flujo directo |

**Tasa de exito:** 8/8 (100%)
**Dentro de 15 s:** 5/8 (62.5%)
**Tiempo promedio:** 16.4 s

**Análisis EC-01:** La meta es >= 80% en <= 15 s. El resultado actual (62.5%) esta por debajo. Las causas identificadas son:
1. Participante 1 no encontró rapidamente el tab "Registrar" (22 s)
2. Participante 3 leyo cada paso con detenimiento (35 s) — caso límite por edad
3. Participante 6 dudo en el campo de precio (18 s)

**Acciones correctivas propuestas:**
- Agregar un boton flotante (FAB) "+" en la vista de comparación para acceso directo al registro
- Pre-seleccionar establecimiento si el usuario ya esta en la vista de un producto
- Agregar placeholder mas explícito en el campo de precio ("Ej: 2350, sin puntos")

### 6.3 Tarea 3 — Interpretar estado

| # | Comprension | Respuesta del participante (parafraseada) |
|---|---|---|
| 1 | Correcta | "Qué el precio puede haber cambiado y hay que ir a ver" |
| 2 | Correcta | "Es un precio viejo que ya no es seguro" |
| 3 | Parcial | "Qué algo esta mal" — necesito explicacion adicional |
| 4 | Correcta | "Qué no se ha actualizado recientemente" |
| 5 | Correcta | "Qué puede que ya no cueste eso" |
| 6 | Correcta | "Qué el precio cambio y no se ha vuelto a reportar" |
| 7 | Correcta | "Qué es viejo" |
| 8 | Correcta | "Qué hay que verificar" |

**Tasa de comprensión:** 7/8 correcta (87.5%), 1/8 parcial
**Observación:** El badge amarillo + la advertencia textual funcionan bien. El participante 3 entendio "algo esta mal" que es parcialmente correcto — la advertencia explícita ("Verifica en la tienda") lo ayudo a completar la interpretacion.

### 6.4 Tarea 4 — Cambiar criterio de orden

| # | Completo | Tiempo | Observaciones |
|---|---|---|---|
| 1 | Si | 8 s | Encontro los botones de orden sin problema |
| 2 | Si | 3 s | Directo |
| 3 | Si | 12 s | Leyo las opciones y eligio correctamente |
| 4 | Si | 2 s | Directo |
| 5 | Si | 5 s | Directo |
| 6 | Si | 6 s | Directo |
| 7 | Si | 4 s | Directo |
| 8 | Si | 3 s | Directo |

**Tasa de exito:** 8/8 (100%)
**Observación:** La barra de orden con botones tipo pill/chip es intuitiva. Todos los usuarios la encontraron rapidamente.

## 7. Evaluación de accesibilidad (EC-08)

### 7.1 Revisión automatizada con axe

Se ejecuto axe-core sobre las 6 vistas del prototipo.

| Vista | Violaciones A | Violaciones AA | Estado |
|---|---|---|---|
| Inicio (`/`) | 0 | 0 | Aprobada |
| Buscar (`/buscar`) | 0 | 0 | Aprobada |
| Comparar (`/comparar/:id`) | 0 | 0 | Aprobada |
| Registrar (`/registrar`) | 0 | 0 | Aprobada |
| Establecimientos | 0 | 0 | Aprobada |
| Perfil | 0 | 0 | Aprobada |

### 7.2 Checklist manual WCAG 2.2 AA

| Criterio | Resultado | Evidencia |
|---|---|---|
| 1.3.1 Info y relaciones | Cumple | Estructura semantica con `<section>`, `<nav>`, `<h1>`, `role` |
| 1.4.3 Contraste mínimo (4.5:1) | Cumple | Texto primario `#111827` sobre `#ffffff` = 15.4:1 |
| 1.4.11 Contraste no textual (3:1) | Cumple | Bordes y badges con contraste verificado |
| 2.1.1 Teclado | Cumple | Todos los elementos interactivos son focusables con Tab |
| 2.4.3 Orden de foco | Cumple | Foco sigue el orden visual de arriba a abajo |
| 2.4.7 Foco visible | Cumple | `:focus-visible` con outline azul de 2px definido en CSS global |
| 2.5.8 Tamano de objetivo (>= 24px) | Cumple | Botones del bottom tab bar >= 44px; botones de accion >= 48px |
| 3.3.1 Identificación de errores | Parcial | Los campos required muestran validación del navegador; falta validación custom |
| 3.3.2 Etiquetas o instrucciones | Cumple | Todos los inputs tienen `<label>` asociado o `aria-label` |
| 4.1.2 Nombre, rol, valor | Cumple | `aria-pressed`, `aria-checked`, `role="radiogroup"`, `aria-label` |

### 7.3 Prueba con lector de pantalla

Se probo el flujo de comparación con TalkBack (Android) en un dispositivo del equipo:
- La navegacion por tabs funciona correctamente (anuncia "Inicio, enlace", "Buscar, enlace", etc.)
- Los badges de vigencia y confianza se leen correctamente
- Las advertencias con `role="alert"` se anuncian automáticamente
- El flujo de registro anuncia "Paso 1 de 5" implicitamente por la barra de progreso (mejora: agregar `aria-label` explícito)

## 8. Hallazgos principales y plan de mejora

| # | Hallazgo | Severidad | Escenario | Acción correctiva | Prioridad |
|---|---|---|---|---|---|
| H-01 | EC-01 no cumple meta (62.5% vs 80% en <= 15 s) | Alta | EC-01 | Agregar FAB "+", pre-seleccionar establecimiento, placeholder explícito | Corte 3 |
| H-02 | Adulto mayor tardo 35 s en registro | Media | EC-01, EC-08 | Agregar texto de guía "Paso X de Y" y tipografia mas grande en pasos | Corte 3 |
| H-03 | Validación de formularios solo nativa del navegador | Media | EC-08 (3.3.1) | Implementar validación inline con mensajes en español | Corte 3 |
| H-04 | Barra de progreso no anuncia paso actual al lector de pantalla | Baja | EC-08 | Agregar `aria-label="Paso ${n} de 5"` a la barra | Corte 3 |
| H-05 | Comprension parcial del badge "Puede estar desactualizado" (1/8) | Baja | EC-06 | Evaluar texto alternativo mas claro | Corte 3 |

## 9. Métricas de calidad — resultado vs umbral

| Metrica | Meta (plan-calidad) | Resultado | Estado |
|---|---|---|---|
| M-01 Registros <= 15 s (EC-01) | >= 80% | 62.5% (5/8) | **No cumple** — plan de mejora definido |
| M-11 Accesibilidad axe A/AA (EC-08) | 0 violaciones | 0 violaciones | Cumple |
| M-09 Neutralidad de comparación (EC-06) | 100% casos | 8/8 participantes vieron criterio de orden | Cumple |

## 10. Conclusiones

1. **El flujo de consulta funciona bien:** 100% de exito, tiempo promedio 12.6 s, incluso para usuarios con poca experiencia tecnologica.

2. **El flujo de registro necesita optimización:** el 62.5% dentro de 15 s esta por debajo de la meta del 80%. Las acciones correctivas (FAB, pre-selección, placeholders) se implementaran en el corte 3.

3. **La comprensión de los estados de vigencia/confianza es buena** (87.5%), lo que valida el diseño de badges + advertencias textuales (EC-03, EC-06).

4. **La accesibilidad es satisfactoria** con 0 violaciones axe y checklist WCAG 2.2 AA mayormente aprobada. El hallazgo H-04 (aria-label en barra de progreso) es una mejora menor.

5. **El criterio de orden transparente** (`criterio: precio_unidad_asc`) fue entendido implicitamente por todos los participantes al cambiar de Precio a Distancia.

## 11. Declaración de uso de inteligencia artificial generativa

Conforme a la guía del proyecto (§12.5), el equipo declara que utilizó el asistente de IA generativa **Claude (Anthropic)** como apoyo en las siguientes actividades:

| Actividad | Uso de la IA | Revisión del equipo |
|---|---|---|
| Estructura del protocolo de prueba | Sugerencia de formato y secciones basadas en ISO/IEC 25066 (usability testing) | El equipo definio las tareas y los criterios de exito |
| Redacción de hallazgos y acciones correctivas | Formato tabular y redacción | Los hallazgos son del equipo basados en la observación directa |
| Checklist WCAG 2.2 AA | Listado de criterios aplicables | El equipo verifico cada criterio contra el prototipo real |

**Compromisos del equipo:** las pruebas se realizaron con participantes reales del entorno del equipo. Los tiempos, respuestas y observaciones son datos reales registrados durante las sesiones. Cada integrante puede explicar y defender los hallazgos y las acciones correctivas propuestas.
