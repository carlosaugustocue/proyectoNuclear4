# Evidencia primaria de la prueba de usabilidad (Cal. E4) y de accesibilidad (EC-08)

> Esta carpeta debe contener los registros **originales** de la prueba descrita en
> [protocolo-prueba-usabilidad.md](../protocolo-prueba-usabilidad.md). Solo el equipo puede aportarlos:
> no se deben reconstruir ni completar a posteriori. Si algún registro no existe, hay que decirlo en el
> protocolo en lugar de generarlo.

| Archivo esperado | Contenido | Estado |
|---|---|---|
| `tiempos-por-participante.csv` | Una fila por participante y tarea: `participante (P1..P8), tarea, inicio, fin, segundos, completó (sí/no), red simulada` | ⬜ Pendiente |
| `sesiones.md` | Fecha, lugar, dispositivo, navegador y moderador de cada sesión | ⬜ Pendiente |
| `consentimientos.md` | Registro de que cada participante dio su consentimiento (sin datos personales: solo P1..P8 y fecha) | ⬜ Pendiente |
| `axe-registro.json`, `axe-comparacion.json` | Reporte exportado de axe DevTools de la prueba del 2026-11-11 (EC-08, M-11) | ⬜ No está en el repositorio. Desde el 2026-11-25 axe corre automatizado en el CI: ver [`sistema-accesibilidad/`](sistema-accesibilidad/README.md) |
| `capturas/` | Capturas de las pantallas evaluadas, si se tomaron | ⬜ Opcional |

Nota: el prototipo evaluado el 2026-11-11 usaba datos simulados. Desde el 2026-11-16 la PWA consume la API real
con los mismos flujos y la misma apariencia, así que una repetición de la prueba (plan de mejora de M-01) puede
hacerse sobre la versión conectada.


## Entrega continua (B2 · EC-12)

Carpeta `b2/`.

| Archivo | Estado | Contenido |
|---|---|---|
| `trivy-web-nginx-1.27-antes.txt` | ✅ | Escaneo local con Trivy 0.69.3: la imagen `web` con `nginx:1.27-alpine` tenía 3 CVE CRITICAL corregibles; la compuerta la habría bloqueado |
| `trivy-web-despues.txt` · `trivy-api-despues.txt` | ✅ | Tras actualizar a `nginx:1.30-alpine` y `apk upgrade`: 0 CRITICAL. En api queda 1 HIGH no explotable (ver `entrega-continua.md` §3) |
| Captura: PR con una prueba rota en rojo | ⏳ equipo | S-12 / M-18 |
| Captura: PR que vuelve a `nginx:1.27-alpine` en rojo | ⏳ equipo | S-12 / M-18 |
| Captura: ejecución de CD con marcas de tiempo (push → humo en staging) | ⏳ equipo | S-13 / M-19 |
| Tabla del ensayo de reversión | ⏳ equipo | S-14 / M-21, en `runbook-reversion.md` §4 |

## Pruebas de sistema y accesibilidad automática (S-xx · A-01 · M-11 · M-12)

Carpeta [`sistema-accesibilidad/`](sistema-accesibilidad/README.md). La genera la suite `e2e/` (Playwright + axe) del CI.

| Archivo | Estado | Contenido |
|---|---|---|
| `README.md` | ✅ | Resultado de S-08, S-09, S-10, S-11, S-15 y A-01, hallazgos corregidos y cómo reproducir |
| `axe-2026-11-25-antes.md` | ✅ | Línea base: 12 violaciones A/AA en 11 pantallas (M-11 no cumplía) |
| `axe-2026-11-25-despues.md` | ✅ | Tras las correcciones: 0 violaciones en 17 pantallas (M-11 cumple) |
| Revisión manual con lector de pantalla y teclado (A-02) | ⏳ equipo | Checklist WCAG 2.2 AA con TalkBack o NVDA |
