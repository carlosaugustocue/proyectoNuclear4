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
| `axe-registro.json`, `axe-comparacion.json` | Reporte exportado de axe DevTools para los dos flujos críticos (EC-08, M-11) | ⬜ Pendiente |
| `capturas/` | Capturas de las pantallas evaluadas, si se tomaron | ⬜ Opcional |

Nota: el prototipo evaluado el 2026-11-11 usaba datos simulados. Desde el 2026-11-16 la PWA consume la API real
con los mismos flujos y la misma apariencia, así que una repetición de la prueba (plan de mejora de M-01) puede
hacerse sobre la versión conectada.
