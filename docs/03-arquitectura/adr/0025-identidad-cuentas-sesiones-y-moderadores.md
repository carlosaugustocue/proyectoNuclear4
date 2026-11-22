# ADR-0025: Identidad: cuentas con consentimiento, sesión con token opaco y moderadores con cuenta

- **Estado:** Propuesto · **Fecha:** 2026-11-22
- **Responsable(s):** equipo (pruebas y CI: Carlos Augusto Aranzazu Jinete)
- **Escenarios relacionados:** EC-03, EC-07 · **Requisitos:** RF-20, RF-22, RF-23, RF-24, RN-07
- **Reemplaza en parte:** ADR-0014 (identidad diferida) y el punto 5 de ADR-0022 (secreto compartido `MODERACION_TOKEN`)
- **Relacionado con:** ADR-0012 (riesgo aceptado de Laravel 11), ADR-0024 (IP real y límites)

## Contexto
ADR-0014 difirió el contexto Identidad, y desde entonces arrastramos tres huecos:
- **Moderación con un secreto compartido.** Para moderar había que pegar `MODERACION_TOKEN` en la consola. La bitácora (RNF-11) registraba siempre «moderacion», no la persona que decidía, y el secreto no se podía revocar para una sola persona.
- **«Suspender cuenta» (RF-20) no existía**, porque no había cuentas que suspender.
- **La reputación (RN-07) dependía de la IP.** Desde ADR-0024 la IP es la real, pero una IP no es una persona: varios usuarios detrás de una NAT comparten reputación, y una misma persona cambia de IP al pasar de wifi a datos.

Además, la Ley 1581 de 2012 (R-01) exige una autorización expresa e informada antes de tratar datos personales, y el derecho a consultarlos, actualizarlos y suprimirlos (RF-22, RF-23).

## Alternativas consideradas
| Tema | Alternativa | A favor | En contra |
|---|---|---|---|
| Sesión | **A. Token opaco aleatorio (256 bits) en una tabla, guardado como SHA-256** | Revocable al instante (cerrar sesión, cambiar contraseña, suspender); sin dependencias nuevas; si se filtra la base, los hashes no sirven como token | Una consulta por petición autenticada (indexada, despreciable a esta escala) |
| | B. JWT firmado | Sin consulta a la base | No se puede revocar antes del vencimiento sin una lista negra, que reintroduce la tabla; más superficie (algoritmos, rotación de claves) |
| | C. Laravel Sanctum | Paquete oficial | Su modelo usa Eloquent y `users`; habría que adaptar el contexto Identidad al paquete y no al revés (DDD, ADR-0002) |
| Dónde guarda el token la PWA | **A. Solo en memoria** | Un XSS no puede leerlo de un almacenamiento persistente; cumple el estándar del equipo | Al recargar la página hay que volver a entrar |
| | B. `localStorage` | La sesión sobrevive a recargas | Cualquier XSS roba la sesión; prohibido por el estándar del equipo |
| | C. Cookie `HttpOnly` | Inaccesible a JavaScript | La PWA y la API están en subdominios distintos de `up.railway.app`, que está en la *Public Suffix List*: son sitios distintos, así que la cookie sería de terceros (`SameSite=None`), y los navegadores la bloquean cada vez más; además exigiría protección CSRF. Se reevalúa con un dominio propio común |
| Contraseñas | **A. Argon2id con `password_hash`** | Recomendación de OWASP; no trunca a 72 bytes como bcrypt | — |
| Moderadores | **A. Rol en la cuenta, asignado por consola (`php artisan identidad:moderador <correo>`)** | El rol nunca se gana desde la API; queda trazado quién decide | El equipo ejecuta un comando por moderador |
| | B. Lista de correos en una variable de entorno | Sin comando | Sin verificación por correo, cualquiera podría registrarse primero con el correo de un moderador y heredar el rol |
| Suspender cuenta | **A. Puerto `SuspensionDeCuentas` en Moderación, implementado por Identidad** | Moderación sigue sin conocer cuentas ni sesiones; el núcleo no depende de Identidad (AR-01) | Un adaptador más |
| | B. Moderación consulta la tabla `cuentas` | Directo | Rompe las fronteras entre contextos |

## Decisión
1. **Cuentas.**
   - La cuenta se crea con correo, contraseña (Argon2id), autorización expresa de tratamiento de datos, fecha del consentimiento y versión de la política (`/privacidad`, versión `2026-11`).
   - Roles: `CIUDADANO` y `MODERADOR`. Estados: `ACTIVA` y `SUSPENDIDA`.
   - El correo se valida en el dominio, sin la regla `email` de Laravel 11, porque esa regla tiene una inyección CRLF sin corregir (GHSA-5vg9-5847-vvmq; ver ADR-0012).
   - Política de contraseña (NIST SP 800-63B): entre 10 y 128 caracteres, no solo números y sin contener el correo. Se devuelven todos los motivos juntos.
2. **Sesión.**
   - Token de 64 caracteres hexadecimales que vence a las 8 horas. La base solo guarda su SHA-256.
   - Se revoca al cerrar sesión, al cambiar la contraseña (salvo la sesión actual) y al suspender la cuenta.
   - Si el correo no existe, el inicio de sesión responde el mismo 401 genérico, y se calcula un hash señuelo para que el tiempo de respuesta tampoco delate qué cuentas existen.
   - Límites: 5 intentos de inicio de sesión por minuto (por IP y correo) y 10 registros por hora por IP.
3. **RF-23.**
   - `GET /cuentas/yo` muestra los datos y el consentimiento, y `PUT /cuentas/yo/contrasena` cambia la contraseña.
   - `DELETE /cuentas/yo` borra la cuenta y sus sesiones. Los aportes se conservan con su `reportado_por`, que es un id opaco: sin la fila de la cuenta ya no identifica a nadie.
4. **Reportes.**
   - La cuenta es opcional, y sin sesión se mantiene el seudónimo por IP de ADR-0024.
   - Con sesión, el reporte queda a nombre de la cuenta (RN-05 y RN-07 por cuenta). Un token inválido responde 401 y no se degrada en silencio a anónimo.
   - Si eso pasa, la PWA olvida la sesión y repite el envío sin token, así que el reporte que estaba en cola no se pierde.
5. **Moderación.**
   - Exige una sesión con rol `MODERADOR`: sin sesión responde 401, y con otro rol 403. Se elimina `MODERACION_TOKEN`.
   - La bitácora guarda el id de la cuenta del moderador.
   - `SUSPENDER_CUENTA` suspende al autor, cierra sus sesiones e invalida la observación. Sobre un reporte anónimo responde 409.
   - La consola recibe `autorConCuenta` (un booleano) para ofrecer la suspensión sin saber quién es el autor (EC-07).
6. **Fronteras (Deptrac):** `Identidad: [Compartido, Precios, Moderacion]`. Ningún contexto del núcleo depende de Identidad.

## Consecuencias
- **Positivas:**
  - Cada decisión de moderación tiene responsable, y un moderador se revoca quitándole el rol.
  - RF-20, RF-22, RF-23 y RF-24 quedan implementados con pruebas: U-11 (dominio) y C-19 (contrato).
  - La reputación sigue a la persona y no a la red.
  - Cumplimiento verificable de la Ley 1581: consentimiento con fecha y versión, consulta y supresión.
- **Negativas:**
  - **No hay verificación por correo**, porque no hay servicio de correo (R-04). Alguien podría registrarse con un correo ajeno. Por eso el rol de moderador nunca se asigna por correo de forma automática, sino por consola, después de confirmar la identidad de la persona fuera de banda.
  - La sesión se pierde al recargar la PWA (consecuencia de guardarla solo en memoria).
  - Un reporte hecho con sesión que queda en la cola sin conexión y se envía después de recargar va como anónimo.
- **Seguimiento:**
  - Promover a los moderadores del equipo en staging y producción con `railway ssh` y `php artisan identidad:moderador <correo>`.
  - Borrar la variable `MODERACION_TOKEN` de Railway, porque ya no se usa.
  - Con un dominio propio común a la PWA y la API, reevaluar la cookie `HttpOnly` con `SameSite=Strict`.
