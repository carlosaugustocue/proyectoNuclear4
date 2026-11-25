# Calidad del proceso y plan de mejora

> Corte 3 · Modelos de Calidad · **Entregable 6**. Fecha: 2026-11-25.
> Reflexión sobre el proceso seguido por el equipo, con referencia **conceptual** a CMMI V3.0 (CMMI Institute, 2023)
> y a ISO/IEC 33020 (marco de medición de la capacidad del proceso, familia ISO/IEC 330xx). Incluye el efecto de la
> opción B2 (entrega continua) del Anexo 1 y el plan de mejora del producto y del proceso.
> Insumos: [plan de aseguramiento](plan-aseguramiento-calidad.md) §7, [informe de evaluación](informe-evaluacion-calidad.md)
> (incidencias I-01 a I-18) y el historial del repositorio.

## 1. Marco de referencia y alcance

**CMMI V3.0** organiza el proceso en *áreas de práctica*, con niveles de capacidad (0 a 3) y niveles de madurez
de la organización (1 a 5). **ISO/IEC 33020** califica cada atributo del proceso (PA) en una escala N-P-L-F:
- **N:** no logrado (0–15 %);
- **P:** parcialmente (> 15–50 %);
- **L:** ampliamente (> 50–85 %);
- **F:** totalmente (> 85–100 %).

**Alcance y límites.** Es una autoevaluación **conceptual**, como pide la guía: no hubo evaluador acreditado,
y las calificaciones son juicio del equipo apoyado en evidencia del repositorio. Un equipo de tres personas
durante un semestre no es una «organización». Por eso solo se discuten las áreas de práctica que el proyecto
ejerció, sin asignar un nivel de madurez formal.

## 2. Medidas del proceso (definidas en el plan §7)

Datos extraídos de GitHub y de `git log` el 2026-11-25. Se excluyen los PR #27 y #28, que fueron PR de prueba de
M-18.

| Medida | Meta del plan | Resultado | Lectura |
|---|---|---|---|
| PR fusionados en `main` | — | 25 | |
| PR con revisión formal registrada en GitHub | 100 % | **7/25 (28 %)**: 6 con comentarios de Sofía y 1 aprobación de Miguel (#15) | ❌ La revisión ocurrió sobre todo fuera de GitHub (sesiones de trabajo) y no quedó registrada |
| PR fusionados por alguien distinto del autor | 100 % | **18/25 (72 %)**: Miguel 9, Sofía 9; el autor fusionó 7 (entre ellos #12, #13, #14, #19, #20 y #25) | ⚠️ |
| Aprobaciones obligatorias en la protección de `main` | ≥ 1 | Hoy **0** (se bajó para fusionar la ampliación); siguen activos los 7 checks y `enforce_admins` | ❌ Restablecer a 1 |
| Commits por tipo (sin merges) | Secuencia `test:` → `feat:` visible | test 42 · docs 38 · feat 35 · fix 12 · ci 5 · chore 3 · refactor 2 · perf 1 | ✅ Evidencia de TDD (más `test:` que `feat:`) |
| Commits con contenido por integrante | Todos (guía §10) | Carlos 138 (varias identidades de Git) · Sofía 1 · Miguel 0 | ❌ **Riesgo alto** para la evaluación individual |
| Tiempo de PR abierto → fusionado | — | La mayoría entre 0 y 13 h | ✅ Ramas cortas |
| Defectos escapados (llegaron a `main`, a producción o al pipeline) | 0 | **6** de 18 incidencias (I-01, I-02, I-07, I-08, I-10, I-12) | ⚠️ 5 de los 6 los detectaron mediciones, auditorías u operación, no las pruebas funcionales |
| Precisión de estimación (M-08) | real/estimado ≤ 1,3 | **Sin medir**: no se llevó registro de horas | ❌ |
| Retrospectiva al cierre de cada corte | 3 actas | 0 actas formales (las lecciones quedaron en los ADR y en este documento) | ❌ |
| Declaración de uso de IA | En cada entregable | Presente en todos los documentos y en `Co-Authored-By` de los commits | ✅ |

## 3. Autoevaluación por área de práctica (CMMI V3.0, conceptual)

| Área de práctica (CMMI) | Proceso afín (ISO/IEC 12207) | Qué hizo el equipo | Evidencia | Nivel de capacidad estimado |
|---|---|---|---|---|
| **Solución técnica (TS)** y **Desarrollo y gestión de requisitos (RDM)** | Definición de la arquitectura, implementación | Escenarios de calidad en seis partes, ADR por decisión, contrato OpenAPI, TDD | 26 ADR, 375 pruebas, OpenAPI 0.7.0 | **3, definido**: práctica estándar documentada y repetida en los tres cortes |
| **Verificación y validación (VV)** | Verificación, validación | Pirámide de pruebas completa en el CI (unitarias, integración, contrato, sistema, accesibilidad); carga y credibilidad reproducibles; una prueba con usuarios | Informe de evaluación §4 | **3 en verificación** · **1–2 en validación** (una sola prueba con usuarios, sin trabajo de campo) |
| **Aseguramiento de la calidad del proceso (PQA)** | Aseguramiento de la calidad | Plan con DoD y medidas de proceso, sin auditar su cumplimiento hasta este documento | Plan §7, este documento | **1, inicial**: la práctica existía en papel, no en la ejecución |
| **Revisión por pares (PR)** | Revisión | PR obligatorio por protección de rama; revisión formal en el 28 % | §2 | **1, inicial** |
| **Gestión de la configuración (CM)** | Gestión de la configuración | Git, ramas por funcionalidad, PR obligatorio, imágenes construidas desde el commit, versiones fijadas (incluso por *digest*) | Historial, `cd.yml` | **2, gestionado** (pasa a 3 con B2, ver §4) |
| **Estimación (EST)** y **Planificación (PLAN)** | Planificación del proyecto | Estimación por componentes en los análisis de impacto, con reestimación antes de empezar | Análisis de impacto | **1, inicial**: no se midió el esfuerzo real |
| **Monitoreo y control (MC)** | Evaluación y control | Pendientes en `CLAUDE.md` y en el README de evidencias; sin tablero ni registro de horas | — | **1, inicial** |
| **Resolución de incidentes y análisis de causas (IRP, CAR)** | Resolución de problemas | Registro de 18 incidencias con causa y tratamiento; las lecciones se volvieron reglas (pruebas sobre `nuclear_test`, PR siempre a `main`) | Informe §6 | **2, gestionado** |
| **Gestión de riesgos (RSK)** | Gestión de riesgos | Riesgos, puntos de sensibilidad y trade-offs en cada documento de arquitectura y en la evaluación ATAM | doc-arq §8, evaluación §7 | **2, gestionado** |

**Atributos de proceso según ISO/IEC 33020 para el proceso de desarrollo en su conjunto:**

| Atributo | Calificación | Justificación |
|---|---|---|
| PA 1.1 Ejecución del proceso | **F** | El producto existe, funciona y está desplegado |
| PA 2.1 Gestión de la ejecución | **P** | Hubo planificación por cortes, pero no seguimiento del esfuerzo ni retrospectivas |
| PA 2.2 Gestión de los productos de trabajo | **L** | Plantillas (ADR, escenarios) y revisión automática en el CI; la revisión humana fue débil |
| PA 3.1 Definición del proceso | **L** | DoD y convenciones de commits, ramas y PR documentadas en el plan y en `CLAUDE.md` |
| PA 3.2 Despliegue del proceso | **P** | La parte automatizada se cumplió siempre; la humana (revisión, horas, retrospectivas) no |

**Lectura:** el proceso es **fuerte donde está automatizado y débil donde depende de la disciplina de las
personas**. Todo lo que exige el CI se cumplió en el 100 % de los PR: pruebas, cobertura ≥ 80 %, PHPStan,
Deptrac, Spectral, Trivy, e2e y accesibilidad. En cambio, la revisión formal, el registro de horas, las
retrospectivas y la distribución del trabajo quedaron muy por debajo de lo que el plan prometía.

## 4. Efecto de la opción B2 (entrega continua) sobre la calidad del proceso

| Dimensión del proceso | Antes de B2 (corte 2) | Con B2 (corte 3) | Evidencia |
|---|---|---|---|
| Controles automáticos de calidad | CI con pruebas, PHPStan, Deptrac, Spectral y build | Se suman **Trivy** (imágenes y secretos), **SonarCloud** (quality gate), **Playwright + axe** contra las imágenes reales y **humo k6** en cada entorno | `ci-reutilizable.yml`, `cd.yml` |
| Capacidad de bloquear defectos | Solo los defectos que rompen pruebas | Defectos funcionales **y** vulnerabilidades: M-18 = 2/2 | PR #27 y #28 |
| Integración | Fusionar a `main` no desplegaba nada; el despliegue era manual | Cada fusión llega a staging en ~5 min (mediana de M-19) | Tabla de M-19 |
| Capacidad de diagnóstico | Logs locales | Artefactos por ejecución (cobertura, reporte axe, resumen k6, SARIF de Trivy), humo con criterio explícito y runbook de reversión | Pestaña Actions |
| Gestión de la configuración (CM) | Nivel 2 | **Nivel 3 conceptual**: el camino a producción está definido como código, es el mismo para todos y queda trazado (quién aprobó, qué SHA) | `cd.yml`, entorno `production` |
| Incidentes detectados gracias a B2 | — | I-05 (CVE CRITICAL), I-07 (fallo del propio CD), I-11 (contraste, por axe en el CI) | Informe §6 |
| Efectos negativos | — | La cola compartida retiene staging mientras producción espera aprobación (I-14); la aprobación depende de dos personas (la promoción del #25 sigue pendiente); M-21 sin ensayar | M-19, R-4 |

**Conclusión sobre B2:** la entrega continua **subió el piso de calidad del proceso de forma medible**. Hoy un
cambio defectuoso o vulnerable no puede llegar a `main`, y lo que antes dependía de la memoria (desplegar,
escanear, probar la imagen real) se volvió un paso obligatorio. B2 **no** resolvió las debilidades humanas del
§3 (revisión, horas, retrospectivas), porque esas no se automatizan. Las dejó más visibles: ahora son lo único
que el pipeline no garantiza.

## 5. Uso de IA generativa y su efecto en el proceso

El equipo usó Claude como asistente de programación y redacción, y reconoce su efecto sobre el proceso:
- **Positivo:** el TDD se volvió barato, las pruebas ganaron velocidad y consistencia, y la documentación se
  mantuvo al día con el código.
- **Negativo:**
  - concentró los commits en una sola cuenta;
  - comprimió el trabajo en pocas jornadas, lo que invalida el historial como medida de esfuerzo (M-08);
  - aumentó el riesgo de que un integrante no pueda **explicar** una decisión que no tomó directamente
    (guía §12.5).

Ese último riesgo es el más importante para la sustentación.

## 6. Plan de mejora

### 6.1 Producto

| # | Mejora | Medida que mueve | Prioridad | Responsable | Criterio de cierre |
|---|---|---|---|---|---|
| P-1 | Servir la API con php-fpm + nginx o FrankenPHP y registrar el ADR del servidor de aplicaciones | M-05, M-06, M-16 (I-13) | **Alta** | Miguel | K-01 con p95 ≤ 2 s y < 1 % de errores; K-02 con p95 ≤ 3 s |
| P-2 | Índice de cobertura y, si hace falta, vista materializada por municipio para la canasta | M-16 | Media | Miguel | K-02 en umbral |
| P-3 | Recalibrar RN-06 (MAD y ventana) con datos reales; que la moderación registre «descuento» como causa | M-04 (I-15) | Alta, tras la Fase 1 | Sofía | M-04 ≤ 0,05 en las 5 semillas y en datos reales |
| P-4 | Repetir la prueba de usabilidad con ≥ 8 personas sobre la PWA rediseñada | M-01 (I-16) | **Alta** | Sofía | M-01 ≥ 0,80 |
| P-5 | Promover a producción el catálogo SIPSA y medir D-05/M-22 cuando haya comercios reales | M-22 | Media | Equipo | D-05 con datos |
| P-6 | Pasar los ADR Propuestos a Aceptado, o registrar por qué siguen así | Trazabilidad | Media | Miguel | 0 ADR sin decisión del equipo |

### 6.2 Proceso

| # | Mejora | Área (CMMI) | Prioridad | Responsable | Criterio de cierre |
|---|---|---|---|---|---|
| Q-1 | Restablecer 1 aprobación obligatoria en `main` y revisar en GitHub con comentarios | PR, PQA | **Alta** | Carlos (configura) · todos (revisan) | 100 % de los PR siguientes con revisión registrada |
| Q-2 | Commits propios de Miguel y Sofía (P-1, P-3, P-4, P-6) y `.mailmap` para unificar identidades | CM, guía §10 | **Alta** | Miguel, Sofía, Carlos | `git shortlog -sn` muestra a los tres |
| Q-3 | Registrar las horas por tarea (hoja simple) y llenar M-08 en los análisis de impacto | EST, MC | Alta | Todos | M-08 calculado |
| Q-4 | Ensayar el runbook de reversión y llenar M-21 | Resolución de incidentes | Alta | Equipo | M-21 ≤ 5 min |
| Q-5 | Separar los grupos de concurrencia `cd-staging` y `cd-produccion`; avisar cuando haya una aprobación pendiente | CM | Media | Carlos | M-19 ≤ 15 min en 10/10 |
| Q-6 | Retrospectiva de 30 min al cierre, con acta breve en `docs/` | PQA | Media | Sofía | Acta en el repositorio |
| Q-7 | Correr K-01/K-02 y la auditoría de datos en el CD (semanal o antes de producción) | VV | Media | Carlos | Ejecución programada en Actions |
| Q-8 | Antes de la sustentación, cada integrante explica a los otros dos un ADR y un módulo que no escribió | Integridad (guía §12.5) | **Alta** | Todos | Sesión hecha |

## 7. Conclusión

El equipo construyó un producto de alta calidad técnica con un proceso de **madurez desigual**. Es definido
(nivel 3) en solución técnica, en verificación y, gracias a B2, en gestión de la configuración. Es inicial
(nivel 1) en revisión por pares, estimación, seguimiento y distribución del trabajo. La lección principal:
**lo que no se automatiza no se cumple bajo presión de tiempo**. Por eso el plan de mejora prioriza esas
prácticas humanas (Q-1, Q-2, Q-3, Q-8) y la brecha de desempeño del producto (P-1).

## 8. Referencias

1. CMMI Institute. (2023). *CMMI V3.0: Model at a glance*. CMMI Institute.
2. ISO/IEC 33020:2019. *Information technology — Process assessment — Process measurement framework for assessment of process capability*. ISO.
3. ISO/IEC/IEEE 12207:2017. *Systems and software engineering — Software life cycle processes*. ISO.
4. Forsgren, N., Humble, J., & Kim, G. (2018). *Accelerate: The science of lean software and DevOps*. IT Revolution.

## 9. Declaración de uso de inteligencia artificial generativa

Este documento se redactó con apoyo de Claude (Anthropic), a partir de datos de GitHub y del historial de Git.
Las calificaciones de capacidad son un juicio que el equipo debe revisar, ajustar y defender en la sustentación
(guía §12.5).
