# ADR-0014: Contexto Comercial como módulo nuevo e identidad diferida

- Estado: Propuesto
- Ampliado por: ADR-0023 (Comercial también puede depender de Comparación y Moderación; ningún contexto depende de Comercial) y ADR-0022 (credencial de comercio mientras Identidad sigue diferida)
- Identidad implementada en: ADR-0025 (cuentas con consentimiento, sesiones y moderadores con cuenta)
- Fecha: 2026-11-16
- Escenarios relacionados: EC-05, EC-06, EC-07

## Contexto

El análisis de impacto del módulo comercial (`analisis-impacto-modulo-comercial.md`) concluye que los comercios
necesitan un perfil verificado para publicar precios oficiales y promociones (corte 3). EC-05 exige que esto
se agregue sin cambios estructurales al núcleo, y EC-06 / ADR-0009 exigen que ninguna relación comercial
altere el orden de la comparación.

Al mismo tiempo, el contexto Identidad (cuentas, JWT, roles, consentimiento Ley 1581) no se construyó en el
corte 2: el núcleo funcional se priorizó sobre las cuentas. El registro de precios necesita, aun así, agrupar
aportes por persona para RN-07 (reputación) y RN-08 (límite de aportes).

## Alternativas consideradas

| Alternativa | Ventajas | Desventajas |
|---|---|---|
| **A. Comercial como contexto nuevo (`app/Domains/Comercial`) que lee el núcleo por su interfaz pública** | Cumple EC-05: el núcleo no se modifica; Deptrac impide que el núcleo dependa de Comercial (AR-02) | Duplica algunos datos de lectura (perfil del comercio) |
| B. Extender `Establecimientos` con campos comerciales | Menos código | Mezcla datos del directorio neutral con datos de una relación comercial; Comparación lee Establecimientos, de modo que la neutralidad dependería solo de la disciplina del equipo |
| C. Servicio separado (microservicio) | Aislamiento fuerte | Contradice ADR-0002 y R-04 (costo operacional) |
| Identidad: **seudónimo HMAC de la IP hasta tener cuentas** | Permite RN-07/RN-08 sin guardar datos personales en claro | Varias personas detrás de la misma IP comparten seudónimo; no hay suspensión individual |
| Identidad: exigir cuenta desde el corte 2 | Reputación real por persona | Bloquea el núcleo y la prueba de usabilidad (EC-01) a la espera de un contexto que no estaba listo |

## Decisión

1. **Comercial será un contexto delimitado propio** (alternativa A). Puede depender de Compartido, Catálogo,
   Establecimientos y Precios; ningún contexto del núcleo puede depender de él. La regla ya está activa en
   `backend/deptrac.yaml` y rompe el CI si se viola.
2. **La identidad queda diferida al corte 3.** Mientras tanto, cada aporte se atribuye a
   `anon-` + HMAC-SHA256(IP, secreto de la aplicación), truncado a 24 caracteres
   (`app/Http/Support/SeudonimoReportante.php`). El seudónimo es interno: no aparece en ninguna respuesta
   pública (prueba C-07). `POST /reportes-precio` acepta solicitudes sin token (contrato 0.2.0).

## Consecuencias

- Positivas: el corte 2 entrega el flujo completo sin bloquearse en cuentas; la neutralidad frente a Comercial
  queda garantizada por estructura y no solo por convención.
- Negativas: la reputación (RN-07) se calcula por seudónimo de IP y no por persona; una red compartida
  (universidad, café) mezcla aportes. La suspensión de cuentas (`CuentaContribuidora::suspendida`) siempre es
  `false` hasta que exista Identidad.
- Seguimiento: pasar a **Aceptado** cuando el equipo revise la decisión; reemplazar el seudónimo por el
  identificador de cuenta al construir Identidad, sin cambiar el contexto Confianza (solo cambia quién produce
  `reportadoPor`).
