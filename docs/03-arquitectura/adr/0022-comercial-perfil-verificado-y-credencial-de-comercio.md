# ADR-0022: El comercio actúa con un perfil verificado por moderación y una credencial propia, sin esperar al contexto Identidad

- **Estado:** Propuesto · **Fecha:** 2026-11-21
- **Responsable(s):** equipo (arquitectura: Miguel Ángel Rodríguez)
- **Escenarios relacionados:** EC-03, EC-05, EC-06, EC-07
- **Relacionado con:** ADR-0014 (identidad diferida), ADR-0015 (precio oficial)

## Contexto
El módulo Comercial (corte 3) permite que un comercio publique precios oficiales y promociones y pida correcciones. Eso exige saber **quién habla en nombre de qué establecimiento**: sin esa garantía, cualquiera podría publicar «precios oficiales» de la competencia (EC-03, R-03).

ADR-0014 difirió el contexto Identidad: hoy los aportes se atribuyen a un seudónimo de IP y no hay cuentas. Construir Identidad completa (registro, contraseñas, consentimiento Ley 1581, recuperación de cuenta) antes del cierre del corte 3 compite con el resto del alcance mínimo y con la ampliación del Anexo 1.

Fuerzas:
- EC-03: un comercio no verificado no puede influir en lo que ve el consumidor.
- EC-05: Comercial debe agregarse sin cambios estructurales en el núcleo.
- EC-07: ninguna respuesta pública puede exponer datos de contacto ni la credencial.
- R-04: equipo de 3 personas y poco tiempo.

## Alternativas consideradas
| Alternativa | A favor | En contra |
|---|---|---|
| A. Construir Identidad completa (cuentas, rol `comercio`, Sanctum) | Solución definitiva, reutilizable por reportantes | Es el contexto más costoso; arrastra Ley 1581 (RF-22, RF-23) y no cabe con el resto del corte 3 |
| **B. Perfil comercial verificado por un moderador + credencial de comercio** | Barato; la verificación humana es justo el control que pide EC-03; no toca el núcleo | Credencial por perfil, no por persona; hay que rotarla a mano |
| C. Sin autenticación: cualquiera publica y la moderación filtra después | Trivial | Viola EC-03 y R-03: la comparación mostraría precios «oficiales» falsos mientras llega la moderación |

## Decisión
Se adopta **B**:

1. El comercio **solicita un perfil** vinculado a un establecimiento que ya existe en el directorio (RF-31). El perfil nace `PENDIENTE` y no puede publicar nada.
2. Un moderador **verifica** el perfil (comprueba por fuera de la plataforma que quien lo pide representa al establecimiento). Al verificarlo, el sistema emite una **credencial de comercio**: un token aleatorio de 40 caracteres que se muestra **una sola vez**. En la base de datos solo se guarda su hash SHA-256.
3. Cada publicación (precio oficial, promoción, corrección) exige `Authorization: Bearer <credencial>`. La credencial solo autoriza a operar sobre **su** establecimiento (RN-15).
4. Un moderador puede **revocar** el perfil. Desde ese momento la credencial deja de funcionar y las promociones activas del perfil dejan de mostrarse.
5. Mientras no exista Identidad, la acción de moderar se protege con una credencial de moderación configurada en el entorno (`MODERACION_TOKEN`), nunca en el repositorio.

Cuando se construya Identidad, la credencial de comercio se reemplaza por una cuenta con rol `comercio`. Solo cambia el adaptador de autenticación (middleware); el dominio Comercial no cambia, porque recibe el `perfilId` ya resuelto.

## Consecuencias
- **Positivas:**
  - EC-03 queda cubierto por una verificación humana explícita.
  - El dominio Comercial no depende de cómo se autentica el comercio.
  - El costo cabe en el corte 3: ~4 h, contra ~16 h estimadas para Identidad completa.
- **Negativas:**
  - La credencial identifica al perfil, no a la persona: varias personas del mismo comercio la comparten.
  - No hay recuperación: si se pierde, el moderador revoca el perfil y emite otro.
  - `MODERACION_TOKEN` es un secreto compartido del equipo, aceptable solo para la demostración académica.
- **Seguimiento:**
  - Pasar a Aceptado cuando el equipo lo revise.
  - Reemplazar ambos tokens por cuentas al construir Identidad (corte 3 tardío o trabajo futuro). Registrarlo como deuda técnica en la evaluación de arquitectura (Arq. E5).
