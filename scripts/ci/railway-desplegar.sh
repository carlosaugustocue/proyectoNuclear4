#!/usr/bin/env bash
# Despliega un servicio en un entorno de Railway y espera a que el despliegue NUEVO quede activo (ADR-0020).
#   uso: scripts/ci/railway-desplegar.sh <servicio> <entorno>
# Requiere RAILWAY_TOKEN (project token del entorno) y RAILWAY_CLI (p. ej. "npx --yes @railway/cli@5.71.0").
# `railway up --ci` termina cuando acaba la construcción; el despliegue puede seguir arrancando,
# así que se consulta `deployment list` hasta que el despliegue creado en esta ejecución quede en SUCCESS.
set -euo pipefail

servicio="${1:?servicio requerido}"
entorno="${2:?entorno requerido}"
cli="${RAILWAY_CLI:?RAILWAY_CLI requerido}"
espera_max="${ESPERA_MAX_S:-600}"
: "${RAILWAY_TOKEN:?RAILWAY_TOKEN requerido}"

inicio="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
commit="${DESPLIEGUE_SHA:-${GITHUB_SHA:-local}}"
echo "▶ ${servicio} → ${entorno} (commit ${commit})"
$cli up --ci --service "$servicio" --environment "$entorno" --message "${commit}"

for ((t = 0; t < espera_max; t += 10)); do
  estado="$($cli deployment list --service "$servicio" --environment "$entorno" --limit 5 --json \
    | python3 -c '
import json, sys
inicio = sys.argv[1]
nuevos = [d for d in json.load(sys.stdin) if d["createdAt"][:19] + "Z" >= inicio]
print(nuevos[0]["status"] if nuevos else "SIN_DESPLIEGUE")' "$inicio")"
  case "$estado" in
    SUCCESS) echo "✓ ${servicio} activo en ${entorno}"; exit 0 ;;
    FAILED | CRASHED | REMOVED) echo "::error::${servicio} terminó en ${estado} en ${entorno}"; exit 1 ;;
    *) echo "  ${servicio}: ${estado} (${t} s)"; sleep 10 ;;
  esac
done
echo "::error::${servicio} no quedó activo en ${espera_max} s"
exit 1
