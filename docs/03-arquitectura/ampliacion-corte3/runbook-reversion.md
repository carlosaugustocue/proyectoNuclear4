# Runbook de reversión (ADR-0020 · EC-12 · M-21 ≤ 5 min)

**Cuándo:** la prueba de humo falla en producción, hay errores 5xx sostenidos o un comportamiento incorrecto visible tras un despliegue.
**Quién:** cualquier integrante con acceso a Railway o a GitHub Actions. Avisar en el canal del equipo **antes** de empezar.

## 0. Decidir (≤ 1 min)
- ¿El problema empezó con el último despliegue? Revisar en Railway → servicio → *Deployments* la hora del despliegue frente a la del primer error (`railway logs --service api --lines 100`).
- Si la causa es una **migración destructiva**, no revertir el código sin mirar el esquema: escalar (ver §3).

## A. Rollback en Railway (preferido: no reconstruye)
1. Railway → proyecto `precios-armenia` → entorno afectado → servicio `api` → *Deployments*.
2. En el último despliegue sano (estado *Removed*, el anterior al actual): menú ⋯ → **Rollback**.
3. Repetir con `worker` y `web` si cambiaron en el mismo despliegue.
4. Verificar con la prueba de humo (no hace falta el workflow Reversión):
   `docker run --rm -v "$PWD/pruebas-calidad/k6:/scripts:ro" -e API_URL=<url>/v1 -e WEB_URL=<url> grafana/k6:1.4.2 run --quiet /scripts/humo-staging.js`

Límite: Hobby retiene las imágenes anteriores 72 h. Pasado ese plazo, usar B.

## B. Redesplegar el commit sano anterior (workflow Reversión)
1. GitHub → Actions → **Reversión** → *Run workflow*.
2. `ref` = SHA del último commit que pasó el pipeline (en Actions → CD, la última ejecución verde). `entorno` = el afectado.
3. En producción, un revisor aprueba (GitHub Environment). El workflow despliega api, worker y web en paralelo, espera a que queden en `SUCCESS`, corre la prueba de humo y escribe la duración en el resumen.

## C. Corregir hacia adelante
Después de A o B: `git revert <commit defectuoso>` en una rama, con PR. El pipeline normal lo lleva a staging y, con aprobación, a producción. Así `main` vuelve a coincidir con lo desplegado.

## 3. Migraciones
- La API migra al arrancar. Revertir el código **no** revierte el esquema.
- Regla del equipo: las migraciones son aditivas (columnas nuevas *nullable*, tablas nuevas). Así el código anterior sigue funcionando con el esquema nuevo y A o B son seguros.
- Si una migración destructiva ya corrió: restaurar el respaldo del volumen de PostgreSQL en Railway antes de revertir. Esto queda fuera de la meta de 5 min; se registra como incidente.

## 4. Registro de ensayos (S-14)
| Fecha | Entorno | Procedimiento | Inicio | Servicio restablecido (humo en verde) | Duración | ¿≤ 5 min? | Responsable |
|---|---|---|---|---|---|---|---|
| | staging | A | | | | | |
| | staging | B | | | | | |
