#!/usr/bin/env bash
# Verifica las cabeceras de seguridad de la PWA (C-18) en la página, el service worker y un asset.
# Uso: verificar-cabeceras-web.sh <url-base-de-la-pwa> <origen-de-la-api>
# Sale con código 1 si falta o difiere alguna cabecera. Lo usan el CI (imagen recién construida)
# y la verificación manual de staging y producción.
set -euo pipefail

base="${1:?url de la PWA}"
api="${2:?origen de la API, p. ej. https://api.example}"
fallos=0

esperar() { # <cabeceras> <nombre> <subcadena esperada> <ruta>
  local valor
  valor=$(printf '%s\n' "$1" | grep -i "^$2:" | head -1 | cut -d' ' -f2- | tr -d '\r' || true)
  if [[ "$valor" != *"$3"* ]]; then
    echo "FALLA $4 · $2: esperado que contenga «$3», recibido «${valor:-<ausente>}»"
    fallos=$((fallos + 1))
  fi
}

asset=$(curl -fsS "$base/" | grep -oE '/assets/index-[^"]+\.js' | head -1)
for ruta in / /sw.js "$asset"; do
  h=$(curl -fsSI "$base$ruta")
  esperar "$h" X-Content-Type-Options nosniff "$ruta"
  esperar "$h" X-Frame-Options DENY "$ruta"
  esperar "$h" Referrer-Policy strict-origin-when-cross-origin "$ruta"
  esperar "$h" Permissions-Policy "camera=()" "$ruta"
  esperar "$h" Content-Security-Policy "script-src 'self'" "$ruta"
  esperar "$h" Content-Security-Policy "connect-src 'self' $api" "$ruta"
  esperar "$h" Content-Security-Policy "frame-ancestors 'none'" "$ruta"
  esperar "$h" Content-Security-Policy "object-src 'none'" "$ruta"
  if printf '%s' "$h" | grep -qi '^server:.*nginx/[0-9]'; then
    echo "FALLA $ruta · Server revela la versión de nginx"; fallos=$((fallos + 1))
  fi
done
# Las cabeceras de caché de cada ruta deben conservarse junto a las de seguridad.
esperar "$(curl -fsSI "$base/sw.js")" Cache-Control no-cache /sw.js
esperar "$(curl -fsSI "$base$asset")" Cache-Control immutable "$asset"

if (( fallos > 0 )); then echo "$fallos cabecera(s) incorrecta(s)"; exit 1; fi
echo "Cabeceras de seguridad de la PWA correctas en /, /sw.js y $asset"
