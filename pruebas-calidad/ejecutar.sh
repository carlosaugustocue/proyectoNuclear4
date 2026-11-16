#!/usr/bin/env bash
# Mediciones automatizadas de calidad en local (corte 2-3).
#
#   EC-03 · M-03 / M-04  detección de anomalías con un conjunto etiquetado (comando calidad:deteccion)
#   EC-02 · M-02         latencia de actualidad por el flujo asíncrono real (k6, i02-actualidad.js)
#   EC-04 · M-05 / M-06  carga de consultas con VUS usuarios concurrentes (k6, k01-carga-comparacion.js)
#
# Levanta un entorno aislado con las imágenes de producción (docker-compose.calidad.yml), corre las
# mediciones, guarda los resultados en resultados/<fecha>/ y genera resumen.md. Al final lo apaga.
#
# Variables: VUS=500 RAMPA=1m DURACION=3m (EC-04 pide 15m) SEMILLAS="1 2 3 4 5" CAMBIOS=20
#            PHP_CLI_SERVER_WORKERS=1 SOLO="ec03 i02 k01" CONSERVAR=0
set -euo pipefail

DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR"

export VUS="${VUS:-500}" RAMPA="${RAMPA:-1m}" DURACION="${DURACION:-3m}" CAMBIOS="${CAMBIOS:-20}"
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-1}"
export UID_GID="$(id -u):$(id -g)"
SEMILLAS="${SEMILLAS:-1 2 3 4 5}"
SOLO="${SOLO:-ec03 i02 k01}"
FECHA="$(date +%Y-%m-%d_%H%M)"
export RESULTADOS="$DIR/resultados/$FECHA"
export CALIDAD_APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
DC=(docker compose -f "$DIR/docker-compose.calidad.yml")

mkdir -p "$RESULTADOS"
log() { printf '\n\033[1m>> %s\033[0m\n' "$*"; }
psql_q() { "${DC[@]}" exec -T db psql -U nuclear -d nuclear -At -c "$1"; }
quiere() { [[ " $SOLO " == *" $1 "* ]]; }

limpiar() {
  if [[ "${CONSERVAR:-0}" != "1" ]]; then
    log "Apagando el entorno de medición"
    "${DC[@]}" --profile k6 down -v --remove-orphans >/dev/null 2>&1 || true
  fi
}
trap limpiar EXIT

log "Entorno: imágenes de producción, PHP_CLI_SERVER_WORKERS=$PHP_CLI_SERVER_WORKERS"
"${DC[@]}" build api
"${DC[@]}" up -d db api worker
for _ in $(seq 1 90); do
  curl -fs "http://127.0.0.1:${CALIDAD_PUERTO:-8090}/v1/productos" >/dev/null 2>&1 && break
  sleep 2
done
curl -fs "http://127.0.0.1:${CALIDAD_PUERTO:-8090}/v1/productos" >/dev/null || { echo "La API no respondió"; "${DC[@]}" logs api | tail -30; exit 1; }

{
  echo "{"
  echo "  \"fecha\": \"$(date -Iseconds)\","
  echo "  \"commit\": \"$(git -C "$DIR" rev-parse --short HEAD 2>/dev/null || echo n/d)\","
  echo "  \"cpu\": \"$(grep -m1 'model name' /proc/cpuinfo | cut -d: -f2 | xargs) ($(nproc) hilos)\","
  echo "  \"memoria_gb\": $(awk '/MemTotal/ {printf "%.1f", $2/1048576}' /proc/meminfo),"
  echo "  \"docker\": \"$(docker version --format '{{.Server.Version}}')\","
  echo "  \"php_cli_server_workers\": $PHP_CLI_SERVER_WORKERS,"
  echo "  \"vus\": $VUS, \"rampa\": \"$RAMPA\", \"duracion\": \"$DURACION\", \"cambios\": $CAMBIOS"
  echo "}"
} > "$RESULTADOS/entorno.json"

if quiere ec03; then
  log "EC-03 · detección de anomalías (semillas: $SEMILLAS)"
  for s in $SEMILLAS; do
    "${DC[@]}" exec -T api php artisan calidad:deteccion --semilla="$s" --salida="/tmp/ec03-$s.json" | tail -3
    "${DC[@]}" cp "api:/tmp/ec03-$s.json" "$RESULTADOS/ec03-semilla-$s.json" >/dev/null
  done
fi

if quiere i02 || quiere k01; then
  log "Datos: semilla de demostración + volumen de EC-04"
  "${DC[@]}" exec -T api php artisan db:seed --force >/dev/null
  "${DC[@]}" exec -T api php artisan db:seed --class=VolumenCargaSeeder --force
  psql_q "SELECT json_agg(id) FROM productos WHERE nombre LIKE '[CARGA]%'" > "$RESULTADOS/productos.json"
fi

if quiere i02; then
  log "EC-02 · latencia de actualidad ($CAMBIOS cambios de precio)"
  IFS='|' read -r PRODUCTO_ID PRESENTACION_ID ESTABLECIMIENTO_ID PRECIO_BASE < <(psql_q "
    SELECT producto_id, presentacion_id, establecimiento_id, precio FROM comparacion_vigente
    WHERE producto_id IN (SELECT id FROM productos WHERE nombre NOT LIKE '[%') AND precio >= 2000
    ORDER BY observado_en DESC LIMIT 1")
  "${DC[@]}" --profile k6 run --rm -e PRODUCTO_ID="$PRODUCTO_ID" -e PRESENTACION_ID="$PRESENTACION_ID" \
    -e ESTABLECIMIENTO_ID="$ESTABLECIMIENTO_ID" -e PRECIO_BASE="$PRECIO_BASE" -e CAMBIOS="$CAMBIOS" \
    k6 run --quiet /scripts/i02-actualidad.js || echo "(I-02 no cumplió algún umbral; ver el resumen)"
fi

if quiere k01; then
  log "EC-04 · carga: $VUS usuarios, rampa $RAMPA, sostenida $DURACION"
  "${DC[@]}" --profile k6 run --rm -e VUS="$VUS" -e RAMPA="$RAMPA" -e DURACION="$DURACION" \
    k6 run --quiet /scripts/k01-carga-comparacion.js || echo "(K-01 no cumplió algún umbral; ver el resumen)"
  "${DC[@]}" logs --no-color api 2>/dev/null | grep -ciE "error|exception" > "$RESULTADOS/api-errores-en-log.txt" || true
fi

python3 -I "$DIR/resumen.py" "$RESULTADOS"
log "Listo: $RESULTADOS/resumen.md"
