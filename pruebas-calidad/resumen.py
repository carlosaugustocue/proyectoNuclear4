"""Genera resumen.md a partir de los JSON de una ejecución de pruebas-calidad/ejecutar.sh."""
import json
import statistics
import sys
from pathlib import Path


def cargar(ruta):
    try:
        return json.loads(ruta.read_text())
    except (OSError, ValueError):
        return None


def veredicto(valor, aceptable, objetivo, menor_es_mejor):
    if valor is None:
        return "sin dato"
    ok = valor <= aceptable if menor_es_mejor else valor >= aceptable
    meta = valor <= objetivo if menor_es_mejor else valor >= objetivo
    return "✅ objetivo" if meta else ("🟡 aceptable" if ok else "❌ no aceptable")


def ms(v):
    return f"{v / 1000:.2f} s" if v >= 1000 else f"{v:.0f} ms"


def seccion_ec03(d):
    corridas = [cargar(p) for p in sorted(d.glob("ec03-semilla-*.json"))]
    corridas = [c for c in corridas if c]
    if not corridas:
        return []
    m03 = [c["M-03"]["valor"] for c in corridas]
    m04 = [c["M-04"]["valor"] for c in corridas]
    filas = [
        "## EC-03 · Detección de anomalías (M-03, M-04)",
        "",
        f"Conjunto **sintético etiquetado** de {corridas[0]['conjunto']['total']} reportes por semilla "
        f"({corridas[0]['conjunto']['anomalos']} anómalos, 20 %), procesado por el caso de uso real. "
        "Detectado = estado `POR_VERIFICAR`.",
        "",
        "| Semilla | M-03 exhaustividad | M-04 falsos positivos | Sutiles marcados | Descuentos legítimos marcados | Anómalos con caso de moderación |",
        "|---|---|---|---|---|---|",
    ]
    for c in corridas:
        t = c["por_tipo"]
        sutil = t.get("sutil", {"marcados": 0, "total": 0})
        desc = t.get("descuento", {"marcados": 0, "total": 0})
        filas.append(
            f"| {c['semilla']} | {c['M-03']['valor']:.3f} | {c['M-04']['valor']:.3f} | "
            f"{sutil['marcados']}/{sutil['total']} | {desc['marcados']}/{desc['total']} | "
            f"{c['anomalos_con_caso_de_moderacion']}/{c['conjunto']['anomalos']} |"
        )
    filas += [
        "",
        "| Medida | Media | Peor caso | Umbral aceptable | Resultado (peor caso) |",
        "|---|---|---|---|---|",
        f"| M-03 | {statistics.mean(m03):.3f} | {min(m03):.3f} | ≥ 0,80 | {veredicto(min(m03), 0.80, 0.90, False)} |",
        f"| M-04 | {statistics.mean(m04):.3f} | {max(m04):.3f} | ≤ 0,05 | {veredicto(max(m04), 0.05, 0.03, True)} |",
        "",
    ]
    return filas


def seccion_i02(d):
    r = cargar(d / "i02-actualidad.json")
    if not r:
        return []
    v = r["metrics"].get("latencia_actualidad", {}).get("values", {})
    sin = r["metrics"].get("cambios_sin_actualizar", {}).get("values", {}).get("count", 0)
    if not v:
        return ["## EC-02 · Latencia de actualidad (M-02)", "", "Sin datos: ningún cambio se reflejó.", ""]
    maximo = v["max"] / 1000
    return [
        "## EC-02 · Latencia de actualidad (M-02)",
        "",
        "Tiempo desde `POST /reportes-precio` hasta que `GET /comparaciones` muestra el precio nuevo, "
        "por el flujo asíncrono real (outbox → worker → Confianza → proyección). Resolución ~200 ms.",
        "",
        "| p50 | p95 | Máximo | Cambios sin reflejar | Umbral aceptable (todos) | Resultado |",
        "|---|---|---|---|---|---|",
        f"| {ms(v['med'])} | {ms(v['p(95)'])} | {ms(v['max'])} | {sin} | ≤ 5 min (objetivo ≤ 1 min) | "
        f"{'❌ no aceptable' if sin else veredicto(maximo, 300, 60, True)} |",
        "",
    ]


def seccion_k01(d, entorno):
    r = cargar(d / "k01-carga.json")
    if not r:
        return []
    m = r["metrics"]
    s = m.get("http_req_duration{fase:sostenida}", {}).get("values", {})
    errores = m.get("http_req_failed", {}).get("values", {}).get("rate")
    reqs = m.get("http_reqs", {}).get("values", {})
    filas = [
        "## EC-04 · Carga de consultas (M-05, M-06)",
        "",
        f"{entorno.get('vus')} usuarios virtuales concurrentes (rampa {entorno.get('rampa')}, sostenida "
        f"{entorno.get('duracion')}), pausa de 1–3 s entre consultas; 2.000 presentaciones y 300 establecimientos. "
        f"Peticiones: {reqs.get('count', 0)} ({reqs.get('rate', 0):.1f}/s).",
        "",
        "| Medida | p50 | p95 | p99 | Máximo | Umbral aceptable | Resultado |",
        "|---|---|---|---|---|---|---|",
    ]
    if s:
        filas.append(
            f"| M-05 tiempo de respuesta (fase sostenida) | {ms(s['med'])} | {ms(s['p(95)'])} | {ms(s['p(99)'])} | "
            f"{ms(s['max'])} | p95 ≤ 2 s | {veredicto(s['p(95)'] / 1000, 2, 1, True)} |"
        )
    if errores is not None:
        filas.append(
            f"| M-06 tasa de error | | | | | < 1 % | {errores * 100:.2f} % · "
            f"{'✅ objetivo' if errores < 0.001 else ('🟡 aceptable' if errores < 0.01 else '❌ no aceptable')} |"
        )
    filas.append("")
    return filas


def seccion_k02(d, entorno):
    r = cargar(d / "k02-canasta.json")
    if not r:
        return []
    m = r["metrics"]
    s = m.get("http_req_duration{fase:sostenida}", {}).get("values", {})
    errores = m.get("http_req_failed", {}).get("values", {}).get("rate")
    reqs = m.get("http_reqs", {}).get("values", {})
    fc = m.get("faltantes_correctos", {}).get("values", {})
    filas = [
        "## EC-11 · Carga de la canasta comparada (M-16, M-17)",
        "",
        f"{entorno.get('vus_canasta')} usuarios virtuales concurrentes (rampa {entorno.get('rampa')}, sostenida "
        f"{entorno.get('duracion')}), canastas de 20 productos al azar, pausa de 2–5 s; 300 establecimientos. "
        f"Peticiones: {reqs.get('count', 0)} ({reqs.get('rate', 0):.1f}/s).",
        "",
        "| Medida | p50 | p95 | p99 | Máximo | Umbral aceptable | Resultado |",
        "|---|---|---|---|---|---|---|",
    ]
    if s:
        filas.append(
            f"| M-16 tiempo de respuesta (fase sostenida) | {ms(s['med'])} | {ms(s['p(95)'])} | {ms(s['p(99)'])} | "
            f"{ms(s['max'])} | p95 ≤ 3 s | {veredicto(s['p(95)'] / 1000, 3, 1.5, True)} |"
        )
    if errores is not None:
        filas.append(
            f"| Tasa de error | | | | | < 1 % | {errores * 100:.2f} % · "
            f"{'✅ objetivo' if errores < 0.001 else ('🟡 aceptable' if errores < 0.01 else '❌ no aceptable')} |"
        )
    if fc:
        total = fc.get("passes", 0) + fc.get("fails", 0)
        filas.append(
            f"| M-17 faltantes señalados y no sumados | | | | | = 1 | {fc.get('passes', 0)}/{total} respuestas · "
            f"{'✅ objetivo' if total and fc.get('fails', 0) == 0 else '❌ no aceptable'} |"
        )
    filas.append("")
    return filas


def main():
    d = Path(sys.argv[1])
    entorno = cargar(d / "entorno.json") or {}
    lineas = [
        "# Resultados de las mediciones de calidad",
        "",
        f"- Fecha: {entorno.get('fecha', 'n/d')} · commit `{entorno.get('commit', 'n/d')}`",
        f"- Equipo: {entorno.get('cpu', 'n/d')}, {entorno.get('memoria_gb', 'n/d')} GB de RAM, Docker {entorno.get('docker', 'n/d')}",
        f"- API: imagen de producción (`docker/railway/api.Dockerfile`), `php artisan serve` con "
        f"PHP_CLI_SERVER_WORKERS={entorno.get('php_cli_server_workers', 'n/d')}; PostgreSQL 16 + PostGIS en el mismo equipo",
        "",
        "> Mediciones en un equipo local, no en Railway: describen el comportamiento de la arquitectura y de la "
        "configuración de producción, pero los tiempos absolutos cambiarán con el hardware del proveedor. "
        "Los datos son sintéticos (prefijos «[CARGA]» y «[EC-03]»).",
        "",
    ]
    lineas += seccion_ec03(d) + seccion_i02(d) + seccion_k01(d, entorno) + seccion_k02(d, entorno)
    (d / "resumen.md").write_text("\n".join(lineas))
    print("\n".join(lineas))


if __name__ == "__main__":
    main()
