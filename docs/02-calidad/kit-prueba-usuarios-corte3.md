# Kit de la prueba con usuarios — corte 3 (US-02)

> Instrumento para **repetir la prueba de usabilidad sobre la aplicación real** (incidencia I-16 del informe de
> evaluación) y para medir la **satisfacción con el SUS** (medida M-23 del plan de calidad).
> Mide: **M-01** (registros ≤ 15 s, EC-01) y **M-23** (puntaje SUS); además registra la tasa de éxito y la
> facilidad percibida por tarea (SEQ).
> Reemplaza las tareas del [protocolo del corte 2](protocolo-prueba-usabilidad.md), que se hizo sobre un prototipo
> con datos simulados. Responsable: Sofía (calidad), con un facilitador y un observador por sesión.

**Regla de integridad:** los resultados solo los aporta el equipo a partir de sesiones reales. Si una sesión no
se hizo o un dato no se tomó, se deja vacío y se explica; no se completa después.

---

## 1. Preparación (una vez, antes de las sesiones)

| Qué | Detalle |
|---|---|
| Participantes | **≥ 8** personas de Armenia que no hayan visto la app. Variedad: ≥ 1 adulto mayor (60+), ≥ 1 tendero o tendera, ≥ 2 con experiencia tecnológica baja. Se identifican solo como P1…P8 |
| Entorno | La app en producción o en staging (URL del README) desde el **celular del participante**, con el navegador que usa siempre. Anotar cuál se usó |
| Red | EC-01 pide red 3G degradada. Si es posible, hacer al menos 2 sesiones con datos móviles en un lugar de baja señal o con limitación de red; si no, registrar «Wi-Fi» y declararlo como limitación |
| Cuenta de prueba | Crear antes una cuenta de prueba por participante (o una compartida) para la tarea 6; no usar datos reales del participante |
| Datos de la sesión | Elegir y anotar en la hoja de sesión un **producto** y un **establecimiento** que existan en el entorno, para las tareas 1, 2 y 6, y 5 productos para la canasta |
| Roles | **Facilitador:** lee el guion y las tareas, no ayuda. **Observador:** cronometra (cronómetro del celular) y toma notas |
| Material | Este kit impreso o en pantalla, las plantillas CSV de la carpeta [`evidencias/prueba-usuarios-corte3/`](evidencias/prueba-usuarios-corte3/) y el formulario SUS (§5) |

## 2. Guion del facilitador

**Bienvenida y consentimiento (leer textual):**

> «Gracias por ayudarnos. Somos estudiantes de Ingeniería de Software de la Universidad Alexander von Humboldt y
> estamos evaluando una aplicación para comparar precios en Armenia. **Estamos probando la aplicación, no a
> usted**: si algo le cuesta, es un problema de la aplicación. La sesión dura unos 15 minutos.
> No vamos a guardar su nombre, su número ni su ubicación: usted será "participante P__". Vamos a anotar cuánto
> tarda en cada tarea y lo que comente. Puede detenerse cuando quiera sin dar explicaciones.
> Estos datos se usan solo para el proyecto académico (Ley 1581 de 2012). ¿Está de acuerdo en participar?»

Anotar en `consentimientos.md` solo: participante, fecha y «sí». Si dice que no, se agradece y no se hace la sesión.

**Durante las tareas:**
- Leer cada tarea en voz alta y entregarla escrita.
- Pedir que **piense en voz alta** («cuénteme qué está buscando»).
- No ayudar. Si pregunta, responder: «¿Qué haría si estuviera solo?». Si lleva **2 minutos** bloqueado en una tarea, se da por no completada y se pasa a la siguiente.
- Después de cada tarea, hacer la pregunta SEQ (§4).

## 3. Tareas

Antes de empezar, el participante está en la pantalla de inicio de la app.

| # | Tarea (leer al participante) | Criterio de éxito | Qué se cronometra / mide |
|---|---|---|---|
| T1 | «Quiere saber dónde está más barato **[producto]** en Armenia. Encuéntrelo y dígame qué tienda tiene el mejor precio.» | Llega a la comparación del producto y nombra la tienda en la posición 1 | Tiempo y éxito |
| T2 | «Está en **[establecimiento]** y ve que **[producto]** cuesta **$[precio]**. Registre ese precio.» | Llega a la confirmación de precio registrado (4 pasos: tienda, producto, precio, confirmar) | **M-01**: desde que toca el botón «Registrar» hasta que ve la confirmación. Éxito si ≤ 15 s |
| T3 | «En la comparación, uno de los precios tiene una advertencia. ¿Qué quiere decir para usted?» | Explica con sus palabras que el precio podría haber cambiado o no está confirmado | Éxito (comprensión), sin tiempo |
| T4 | «Ahora quiere ver primero las tiendas que le quedan más cerca. Cambie el orden.» | Usa «Distancia» y acepta (o explica por qué rechaza) el permiso de ubicación | Tiempo y éxito |
| T5 | «Va a hacer el mercado. Arme una canasta con estos 5 productos: **[lista]**, y dígame en qué tienda le sale más barato todo.» | Agrega los 5 productos y nombra la tienda con menor total; entiende si a alguna tienda le falta un producto | Tiempo y éxito |
| T6 | «Inicie sesión con esta cuenta de prueba. Usted acaba de ver que el precio de **[producto]** en **[establecimiento]** sigue siendo el mismo: confírmelo.» | Inicia sesión y usa «Sigue igual» sobre ese precio | Tiempo y éxito |

T2 es la tarea crítica (EC-01). Si el participante comete un error y lo corrige, el tiempo sigue corriendo; si
registra un precio equivocado sin darse cuenta, la tarea cuenta como no completada.

## 4. Pregunta después de cada tarea (SEQ)

> «En general, ¿qué tan fácil o difícil fue esta tarea?»
> **1** = Muy difícil · 2 · 3 · 4 · 5 · 6 · **7** = Muy fácil

Se anota en la hoja de tiempos. Un promedio por tarea < 5,5 señala la tarea como problemática (Sauro y Lewis, 2016).

## 5. Cuestionario SUS (al terminar todas las tareas)

> «Para terminar, marque qué tan de acuerdo está con cada frase, pensando en la aplicación que acaba de usar.»
> **1** = Totalmente en desacuerdo · 2 · 3 · 4 · **5** = Totalmente de acuerdo
> Responda todas, con la primera impresión.

| # | Afirmación | 1 | 2 | 3 | 4 | 5 |
|---|---|---|---|---|---|---|
| 1 | Creo que me gustaría usar esta aplicación con frecuencia. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 2 | Encontré la aplicación innecesariamente complicada. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 3 | Pensé que la aplicación era fácil de usar. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 4 | Creo que necesitaría la ayuda de una persona con conocimientos técnicos para poder usar esta aplicación. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 5 | Encontré que las distintas funciones de la aplicación estaban bien integradas. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 6 | Pensé que había demasiadas inconsistencias en la aplicación. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 7 | Imagino que la mayoría de las personas aprenderían a usar esta aplicación muy rápido. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 8 | Encontré la aplicación muy engorrosa de usar. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 9 | Me sentí muy seguro(a) usando la aplicación. | ☐ | ☐ | ☐ | ☐ | ☐ |
| 10 | Necesité aprender muchas cosas antes de poder empezar a usar la aplicación. | ☐ | ☐ | ☐ | ☐ | ☐ |

Versión en español adaptada de Brooke (1996). No se cambia el orden ni se omiten afirmaciones: las impares son
positivas y las pares negativas a propósito.

**Cálculo del puntaje (0 a 100) por participante:**
1. Afirmaciones impares (1, 3, 5, 7, 9): valor marcado − 1.
2. Afirmaciones pares (2, 4, 6, 8, 10): 5 − valor marcado.
3. Sumar los 10 resultados (0 a 40) y **multiplicar por 2,5**.

En una hoja de cálculo, con las respuestas en las columnas B a K:
`=((B2-1)+(5-C2)+(D2-1)+(5-E2)+(F2-1)+(5-G2)+(H2-1)+(5-I2)+(J2-1)+(5-K2))*2,5`

**M-23** es el **promedio** de los puntajes de todos los participantes. Interpretación de referencia (Bangor,
Kortum y Miller, 2009; Sauro y Lewis, 2016): 68 es el promedio de la industria; ≥ 80,3 es excelente. El puntaje
no es un porcentaje.

## 6. Preguntas abiertas (2 minutos)

1. ¿Qué fue lo más difícil o confuso?
2. Si pudiera cambiar una cosa de la aplicación, ¿cuál sería?
3. ¿Usaría esta aplicación antes de hacer mercado? ¿Por qué sí o por qué no?

Se anotan textuales y cortas en `notas.md`, sin datos que identifiquen al participante.

## 7. Registro y análisis

| Archivo (en `evidencias/prueba-usuarios-corte3/`) | Contenido |
|---|---|
| `sesiones.md` | Fecha, lugar (barrio o tipo de lugar, no dirección), dispositivo, navegador, red, URL y entorno, facilitador y observador |
| `consentimientos.md` | P1…Pn, fecha y «sí» |
| `tiempos-por-participante.csv` | Copiar de la plantilla: una fila por participante y tarea |
| `sus-respuestas.csv` | Copiar de la plantilla: una fila por participante con las 10 respuestas y el puntaje |
| `notas.md` | Comentarios en voz alta y respuestas abiertas, por participante |

**Resultado que se reporta** (en el protocolo de usabilidad, nueva sección «Repetición del corte 3», y en el
informe de evaluación, M-01 y M-23):

| Medida | Cálculo | Umbral aceptable |
|---|---|---|
| M-01 | Intentos de T2 completados en ≤ 15 s / intentos de T2 | ≥ 0,80 |
| M-23 | Promedio SUS | ≥ 68 |
| Éxito por tarea | Completadas / participantes, por tarea | Informativo |
| SEQ por tarea | Promedio, por tarea | < 5,5 señala un problema |

Con 8 participantes, informar también el puntaje SUS mínimo y máximo, y no presentar decimales que sugieran más
precisión de la que hay. Comparar M-01 con el 62,5 % del corte 2, indicando que el entorno cambió (prototipo con
datos simulados frente a la app real).

## 8. Referencias

1. Brooke, J. (1996). SUS: A "quick and dirty" usability scale. En P. W. Jordan, B. Thomas, B. A. Weerdmeester y I. L. McClelland (Eds.), *Usability evaluation in industry* (pp. 189–194). Taylor & Francis.
2. Bangor, A., Kortum, P., & Miller, J. (2009). Determining what individual SUS scores mean: Adding an adjective rating scale. *Journal of Usability Studies, 4*(3), 114–123.
3. Sauro, J., & Lewis, J. R. (2016). *Quantifying the user experience: Practical statistics for user research* (2nd ed.). Morgan Kaufmann.
4. ISO/IEC 25019:2023. *Systems and software engineering — SQuaRE — Quality-in-use model*. ISO.

## 9. Declaración de uso de inteligencia artificial generativa

El kit se redactó con apoyo de Claude (Anthropic). Las sesiones, los datos y su análisis los hace el equipo.
