# Evaluación de la arquitectura (inspirada en ATAM) y respuesta al cambio

> Corte 3 · Arquitectura de Software II · **Entregable 6**. Fecha: 2026-11-25 · sistema evaluado: `main` @ `f4c84a7`.
> Acompaña al [documento de arquitectura final](documento-arquitectura-final.md) y al registro completo de
> [ADRs](adr/). Las mediciones citadas están en el [informe de evaluación de calidad](../02-calidad/informe-evaluacion-calidad.md).

## 1. Método

Se aplicó una versión reducida de ATAM (Bass, Clements y Kazman, 2021, cap. 21), adecuada a un equipo de tres
personas sin evaluadores externos:

| Paso ATAM | Cómo se hizo |
|---|---|
| 1–2. Presentar el método y los impulsores del negocio | §2, a partir de la guía y del planteamiento del problema |
| 3. Presentar la arquitectura | Documento de arquitectura final (C4 niveles 1 a 3, despliegue) |
| 4. Identificar los enfoques arquitectónicos | §3: diez enfoques, cada uno con su ADR |
| 5. Árbol de utilidad | §4: el del corte 1, ahora con el **resultado medido** de cada escenario |
| 6. Analizar los enfoques frente a los escenarios | §5: escenarios ★, más EC-11 y EC-12 de la ampliación |
| 7–8. Lluvia de escenarios y nuevo análisis | §6: escenarios surgidos de las incidencias reales del proyecto |
| 9. Presentar resultados | §7 (puntos de sensibilidad, trade-offs, riesgos, no-riesgos y temas de riesgo) y §10 |

**Limitación declarada:** no participaron partes interesadas externas (comercios ni usuarios). Los escenarios y
sus prioridades salen de la guía y del equipo. A cambio, cada respuesta se contrasta con una **medición
reproducible** y no solo con un juicio experto.

## 2. Impulsores del negocio y restricciones

- **Credibilidad de los precios colaborativos.** Si los usuarios no confían en el dato, la plataforma no sirve.
- **Neutralidad.** Ninguna relación comercial puede alterar el orden de la comparación (R-02, R-03, guía §12).
- **Registro en campo**, frente a la góndola y con red degradada.
- **Evolución.** El módulo comercial (corte 3) y la ampliación del Anexo 1 deben entrar sin rehacer el núcleo.
- **Restricciones:** tres personas, un semestre y nube gratuita o académica (R-04); Ley 1581 (R-01); sin
  scraping (R-06).

## 3. Enfoques arquitectónicos identificados

| Id | Enfoque | ADR |
|---|---|---|
| AA-1 | Monolito modular con 11 contextos delimitados y fronteras verificadas por Deptrac | 0002, 0016 |
| AA-2 | CQRS ligero: modelo de lectura `comparacion_vigente` y outbox transaccional con worker | 0003 |
| AA-3 | PWA offline-first: cola en IndexedDB, `Idempotency-Key` y reintento exponencial | 0006 |
| AA-4 | Modelo de confianza en un contexto propio: MAD, corroboración y reputación, con moderación | 0007 |
| AA-5 | Neutralidad por construcción: orden declarado (`criterioOrden`); las promociones van en un bloque aparte y llegan por un **puerto** que Comparación define y Comercial implementa | 0009, 0023 |
| AA-6 | El precio oficial es una observación más (fuente `COMERCIO`) por el mismo flujo de confianza | 0015 |
| AA-7 | Canasta como contexto nuevo que calcula en consulta, sin persistir en el servidor | 0017, 0018 |
| AA-8 | Entrega continua con compuertas: CI reutilizable, Trivy, SonarCloud, staging con humo, producción con aprobación | 0020, 0021 |
| AA-9 | Plataforma como servicio (Railway) con las mismas imágenes en local, e2e, staging y producción | 0011, 0013, 0020 |
| AA-10 | Servidor de aplicaciones `php artisan serve` en la imagen de producción | **Sin ADR** (decisión implícita; ver §9) |

## 4. Árbol de utilidad con resultados medidos

```
Utilidad
├── Credibilidad de los datos
│   ├── EC-03 Reportes falsos ................... (A, A) ★  🟡 M-03 0,86 ✅ · M-04 0,075 ❌
│   └── EC-02 Actualidad del precio ............. (A, M) ★  ✅ p95 1,35 s (meta ≤ 5 min)
├── Desempeño
│   ├── EC-04 500 usuarios, p95 ≤ 2 s ........... (A, M) ★  ❌ p95 26,4 s · 12,9 % errores
│   └── EC-11 Canasta, 200 usuarios, p95 ≤ 3 s .. (A, M)    ❌ p95 8,4 s · M-17 ✅
├── Capacidad de interacción
│   ├── EC-01 Registro en ≤ 15 s ................ (A, M) ★  ❌ 62,5 % (corte 2; rediseño sin re-medir)
│   └── EC-08 Accesibilidad WCAG 2.2 AA ......... (M, B)    ✅ 0 violaciones axe
├── Fiabilidad
│   └── EC-09 Registro sin conexión ............. (A, A) ★  ✅ 3/3 sin duplicados
├── Mantenibilidad
│   ├── EC-05 Incorporar el módulo comercial .... (A, A) ★  ✅ M-07 = 0 · ⏳ M-08
│   ├── EC-10 Regresiones detectadas ............ (M, B)    ✅ PR #27 bloqueado
│   └── EC-12 Pipeline con controles ............ (A, M)    🟡 M-18 2/2 · M-19 mediana 4:48 · M-21 ⏳
├── Adecuación funcional
│   └── EC-06 Neutralidad ....................... (A, B)    ✅ M-09 = 1
└── Seguridad
    └── EC-07 Privacidad del reportante ......... (A, M)    ✅ M-10 = 0 · D-06 = 0
```

## 5. Análisis de los escenarios prioritarios

### EC-03 · Credibilidad (AA-4, AA-2)
- **Respuesta de la arquitectura:** la evaluación ocurre en el worker y fuera de la ruta de la petición. La
  observación se guarda siempre (trazabilidad) y solo su **publicación** depende del estado de confianza. Los
  casos POR_VERIFICAR abren moderación.
- **Puntos de sensibilidad:** SP-1, umbral MAD (3,5) y tamaño mínimo de muestra (n ≥ 5); SP-2, tolerancia
  (±5 %) y ventana (48 h) de la corroboración. Mover cualquiera de estos valores desplaza M-03 y M-04 en
  direcciones opuestas.
- **Trade-off TO-1:** detectar más anomalías sutiles (M-03) a costa de marcar descuentos legítimos (M-04). La
  peor semilla marcó 10 de 15 descuentos reales.
- **Riesgo R-1:** los umbrales ⚑ no tienen evidencia de campo (ADR-0007 en Propuesto) y el conjunto etiquetado
  es sintético.
- **No-riesgo NR-1:** cambiar la regla es barato, porque vive en un contexto aislado con 100 % de cobertura. El
  PR #27 lo demuestra: un cambio de tolerancia rompe 3 pruebas antes del merge.

### EC-02 · Actualidad (AA-2)
- **Respuesta:** outbox en la misma transacción → worker → proyección. Medido: p50 1,09 s y p95 1,35 s, con 0
  cambios sin reflejar.
- **SP-3:** intervalo de sondeo del worker (3 s) y disponibilidad del worker. Si el worker se detiene, la
  comparación queda congelada pero **sigue respondiendo** (la lectura está desacoplada).
- **NR-2:** el margen frente a la meta de 5 min es de dos órdenes de magnitud; la consistencia eventual no es
  un problema para el usuario.

### EC-04 · Desempeño (AA-2, AA-9, AA-10)
- **Respuesta medida:** con 20 usuarios, p95 162 ms (corte 2), así que el modelo de lectura no es el límite.
  Con 500 VU y 4 procesos `artisan serve`: p95 26,4 s, 12,9 % de errores y 38,5 pet/s.
- **SP-4:** número de procesos PHP y modelo de ejecución (un proceso por petición, sin *keep-alive* ni
  concurrencia real).
- **Riesgo R-2 (principal hallazgo de la evaluación):** **AA-10 nunca fue una decisión registrada**.
  `artisan serve` entró en la imagen de Railway por simplicidad y nadie lo evaluó contra EC-04 hasta medir. El
  estilo arquitectónico (AA-1, AA-2) **no** es la causa.
- **Tratamiento:** php-fpm + nginx, o FrankenPHP/Octane, en la misma imagen. Es un cambio de infraestructura que
  no toca los contextos. Después, repetir K-01 con el generador de carga en otra máquina.

### EC-09 · Registro sin conexión (AA-3)
- **Respuesta:** cola local con clave de idempotencia y reintento exponencial (2 s … 60 s). S-09: 3/3
  sincronizados sin duplicados, en el CI y contra las imágenes reales.
- **SP-5:** la deduplicación depende de que el cliente genere la clave **una sola vez** por reporte.
- **NR-3:** RN-08 (reemplazo del mismo producto, establecimiento y día) es una segunda barrera contra
  duplicados en el servidor.

### EC-01 · Registro en ≤ 15 s (AA-3)
- **Respuesta:** el registro no espera al servidor; confirma al guardar en la cola local. Lo que falló en el
  corte 2 (62,5 %) fue la **interacción** (encontrar el producto, escribir el precio), no la arquitectura.
- **Riesgo R-3:** con 126 productos, encontrar el producto pesa más. El rediseño (PR #26) añade filtro,
  búsqueda por marca y acciones fijas, pero **falta medir de nuevo**.

### EC-05 · Incorporar el módulo comercial (AA-1, AA-5, AA-6) — ver §8
- **SP-6:** el contenido de `Compartido` (núcleo compartido). Todo cambio ahí afecta a los 11 contextos.
- **TO-2:** tratar el precio oficial como observación (AA-6) reutiliza todo el flujo y conserva la neutralidad.
  El costo: un comercio verificado **no** publica al instante si su precio es anómalo. Se eligió así a
  propósito (ADR-0015).

### EC-06 · Neutralidad (AA-5)
- **Respuesta:** el orden se calcula solo con el criterio declarado. Las promociones van en
  `contenidoPromocional`, nunca en `items`. Deptrac impide que Comparación y Canasta dependan de Comercial (AR-02).
- **NR-4:** la neutralidad está **en la estructura** (la dirección de las dependencias), no solo en una prueba.
  Si alguien hace que el orden lea datos comerciales, el CI falla.

### EC-11 · Canasta comparada (AA-7, AA-10)
- **Respuesta:** cálculo en consulta sobre `ConsultarPreciosVigentes`. La corrección del N+1 (I-06) bajó el p95
  de 25,2 s a 9,9 s y luego a 8,4 s. Sigue fuera del umbral de 3 s por la misma causa que EC-04.
- **SP-7:** productos × establecimientos por consulta (límite de 20 productos, filtro por municipio).
- **TO-3:** calcular en consulta (dato siempre actual, nada que invalidar, sin datos personales en el servidor)
  frente a precalcular (latencia mínima). Se mantiene la decisión. Si M-16 sigue fuera después de cambiar el
  servidor de aplicaciones, el siguiente paso es una vista materializada por municipio.

### EC-12 · Pipeline con controles (AA-8, AA-9)
- **Respuesta:** M-18 = 2/2 (una prueba rota y una imagen vulnerable, ambas bloqueadas antes de fusionar), M-20 = 0
  en `main` y M-19 con mediana de 4 min 48 s.
- **SP-8:** staging, producción y reversión **comparten** el grupo de concurrencia `cd-main`.
- **Riesgo R-4:** una promoción a producción que espera aprobación **retiene** los despliegues a staging (3 de 10
  pasaron de 15 min). Hoy mismo, producción sigue sin el catálogo SIPSA porque su aprobación está pendiente.
- **TO-4:** la aprobación manual da control humano y cumple el Anexo, a costa del tiempo de entrega.
- **Riesgo R-5:** Railway construye su propia imagen a partir del mismo Dockerfile y commit. No es, byte a byte,
  el artefacto que escaneó Trivy (trade-off aceptado en ADR-0020).

## 6. Escenarios que surgieron durante el proyecto (lluvia de escenarios)

| Escenario nuevo | Origen | Respuesta de la arquitectura | Lección |
|---|---|---|---|
| La plataforma, tras un proxy, ve una sola IP para todos | I-08, producción | El límite por IP bloqueaba a todos; se corrigió con proxies de confianza (ADR-0024) | Los supuestos de red cambian al pasar a PaaS |
| Un framework guarda datos personales por su cuenta | I-12, auditoría D-06 | La tabla `sessions` guardaba la IP; se eliminó | La privacidad por diseño necesita **auditar** el esquema, no solo probar las respuestas |
| Una dependencia de dominio quedó en `require-dev` | I-01, carga | La imagen de producción respondía 500 | Probar con **la misma imagen** que se despliega (de aquí nació el stack e2e) |
| Un PR fusionado en una rama intermedia «se pierde» | I-10 | El CI solo corre en PR a `main` | Proceso: los PR van siempre contra `main` |

## 7. Resultados del análisis

**Puntos de sensibilidad:** SP-1 a SP-8 (§5).

**Trade-offs:**

| Id | Se gana | Se cede | Decisión |
|---|---|---|---|
| TO-1 | Exhaustividad de detección (M-03) | Falsos positivos (M-04) | Mantener los umbrales hasta tener datos de campo |
| TO-2 | Neutralidad y un solo flujo (precio oficial = observación) | Inmediatez para el comercio | Mantener (ADR-0015) |
| TO-3 | Actualidad y privacidad (canasta calculada en consulta y guardada en el dispositivo) | Latencia | Mantener; vista materializada si hace falta |
| TO-4 | Control humano en producción | Tiempo de entrega | Mantener, pero separar la cola de staging |
| TO-5 | Simplicidad operativa del monolito modular (una imagen, una base) | Escalado independiente por contexto | Mantener: el cuello de botella no es el estilo |

**Riesgos:**
- R-1: umbrales sin evidencia de campo.
- **R-2: servidor de aplicaciones no evaluado.**
- R-3: usabilidad del registro sin volver a medir.
- R-4: cola compartida del CD.
- R-5: imagen reconstruida por el proveedor.

**No-riesgos:**
- NR-1: cambiar las reglas de confianza es barato.
- NR-2: la consistencia eventual cumple con amplio margen.
- NR-3: doble barrera contra duplicados.
- NR-4: neutralidad estructural.
- NR-5: el núcleo absorbió dos cambios de alcance sin modificaciones estructurales (§8).

**Temas de riesgo (ATAM, paso 9):**
1. **Decisiones de infraestructura sin ADR** (R-2 y el supuesto de red de I-08). Las decisiones de *dominio* se
   registraron y evaluaron con cuidado; las de *operación* entraron por defecto.
2. **Umbrales y datos sin validación de campo** (R-1, R-3; ADR-0007 y ADR-0010 en Propuesto).
3. **Dependencia de la aprobación humana** en el camino a producción (R-4).

## 8. Respuesta de la arquitectura al cambio (EC-05): estimado frente a real

### 8.1 Cambios estructurales al núcleo (M-07) — medido

Se comparó cada *merge commit* con su primer padre, en los contextos del núcleo: Precios, Confianza, Comparación,
Catálogo, Establecimientos y Compartido.

| Cambio | PR | Archivos del cambio | Archivos del núcleo tocados | Tipo | M-07 |
|---|---|---|---|---|---|
| A1 Canasta comparada | #12 | 43 (+2.161 líneas) | 1 **nuevo**: `Comparacion/Application/ConsultarPreciosVigentes.php` | Punto de extensión previsto en el análisis | **0** |
| Módulo Comercial | #13 | 65 (+2.727 líneas) | 2 **nuevos** (`PromocionesVigentes`, `SinPromociones`) y 1 **modificado**: `ConsultarComparacion` (+1 parámetro con valor por defecto, +1 línea que llena `contenidoPromocional`) | Puerto previsto desde el corte 1 (el campo existía «reservado para el corte 3») | **0** |
| B2 Entrega continua | #14, #15 | Pipeline e infraestructura | 0 | No toca el producto | **0** |

En los tres casos Deptrac terminó con 0 violaciones, y ningún ADR anterior se reemplazó ni se deprecó.
**El núcleo absorbió los dos cambios de alcance sin modificaciones estructurales.**

### 8.2 Precisión de la estimación (M-08) — pendiente de las horas reales

| Cambio | Estimado antes de construir | Real | Umbral M-08 (real ≤ 1,3 × estimado) |
|---|---|---|---|
| Módulo Comercial | 37 h (línea base, 2026-11-11) · 43 h (reestimación, 2026-11-21) | **Por registrar** | ≤ 48,1 h frente a la base · ≤ 55,9 h frente a la reestimación |
| A1 Canasta comparada | 44,5 h | **Por registrar** | ≤ 57,9 h |
| B2 Entrega continua | 27 h | **Por registrar** | ≤ 35,1 h |
| **Ampliación (A1 + B2)** | **71,5 h** | **Por registrar** | ≤ 93,0 h |

**Por qué todavía no hay cifra real.** Las columnas «Real (h)» de los dos análisis de impacto se llenan al
cierre con el registro de horas del equipo. El historial de Git **no sirve como sustituto**: los dos cambios se
construyeron con mucha ayuda de IA generativa, en una sola jornada (2026-11-21) y en ramas apiladas que se
publicaron juntas. Los commits de la canasta, por ejemplo, abarcan menos de una hora de reloj. Esa cifra no es
esfuerzo humano, y presentarla como «real» no sería honesto.

**Lectura anticipada.** El cociente real/estimado seguramente quedará **por debajo de 1**. Eso no significa
que la estimación fuera buena, sino que **no contemplaba el uso de IA**. La IA abarata la escritura del código,
pero no la revisión, las pruebas ni la comprensión.

La reestimación del comercial subió de 37 h a 43 h (1,16) **antes** de empezar:
- **Tareas que no se vieron en el corte 2:** la credencial del comercio y la vía de corrección por moderación.
- **Lo que sobró:** el listener propio, que ADR-0015 hizo innecesario.

Es decir, el error de estimación vino del **diseño todavía abierto**, no de la implementación.

## 9. Decisiones que el equipo cambiaría y por qué

| Decisión | Qué haríamos distinto | Por qué |
|---|---|---|
| `artisan serve` en producción (AA-10, sin ADR) | Registrar un ADR del servidor de aplicaciones desde el corte 2 y elegir php-fpm + nginx o FrankenPHP | Es la causa de los tres incumplimientos de desempeño (M-05, M-06, M-16). Medir antes de desplegar lo habría mostrado |
| Fijar los umbrales ⚑ antes de la Fase 1 | Hacer las entrevistas y la encuesta antes de fijar la vigencia, el MAD y la ventana de corroboración | M-04 depende de esos valores; con datos reales se calibran en vez de suponerse |
| Identidad diferida con seudónimo por IP (ADR-0014) | Construir una Identidad mínima desde el corte 2 | El seudónimo por IP falló tras el proxy (I-08) y obligó a migrar los aportes cuando llegaron las cuentas |
| Un solo grupo de concurrencia para staging y producción | Separar `cd-staging` de `cd-produccion` | Una aprobación pendiente retiene staging (R-4, M-19) |
| Ramas apiladas para la ampliación | PR pequeños contra `main`, uno por tramo | El CI solo corre en PR a `main` (I-10), y la revisión de un PR de 65 archivos es superficial |
| **Lo que sí repetiríamos:** monolito modular con Deptrac, outbox + modelo de lectura, puerto para las promociones, precio oficial como observación | — | Son los enfoques que explican M-07 = 0, EC-02 con amplio margen y la neutralidad estructural |

## 10. Conclusión

La arquitectura **cumplió su objetivo principal de modificabilidad**. Dos cambios de alcance (el módulo
comercial y la ampliación) entraron sin ningún cambio estructural al núcleo, gracias a puntos de extensión
previstos desde el corte 1 y verificados por Deptrac en cada PR. La credibilidad, la actualidad, la privacidad y
la neutralidad se cumplen por construcción y con evidencia.

El riesgo dominante **no está en el estilo arquitectónico, sino en una decisión de infraestructura que nunca se
registró**: el servidor de aplicaciones. Esa es la principal lección del proyecto: el rigor de los ADR de dominio
también debe aplicarse a la operación. Una decisión sin un escenario que la respalde es un riesgo, aunque parezca
un detalle.

## 11. Declaración de uso de inteligencia artificial generativa

La evaluación y su redacción se hicieron con apoyo de Claude (Anthropic). Los datos provienen de mediciones y
del historial del repositorio, citados en cada sección. El equipo debe revisarla y poder defenderla en la
sustentación (guía §12.5).
