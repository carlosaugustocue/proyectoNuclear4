/* Utilidades puras del mapa de establecimientos (sin depender de Leaflet, para poder probarlas). */
export interface Punto {
  lat: number
  lon: number
}

/** Centro de Armenia (Quindío): se usa cuando no hay establecimientos que encuadrar. */
export const CENTRO_ARMENIA: Punto = { lat: 4.5339, lon: -75.6811 }

/** Margen en grados (~550 m) alrededor de un único punto. */
const MARGEN_UN_PUNTO = 0.005

function esValido(p: Punto): boolean {
  return Number.isFinite(p.lat) && Number.isFinite(p.lon) && Math.abs(p.lat) <= 90 && Math.abs(p.lon) <= 180
}

/** Rectángulo [[sur, oeste], [norte, este]] que contiene todos los puntos válidos; null si no hay ninguno. */
export function limitesDe(puntos: Punto[]): [[number, number], [number, number]] | null {
  const validos = puntos.filter(esValido)
  if (validos.length === 0) return null
  const lats = validos.map((p) => p.lat)
  const lons = validos.map((p) => p.lon)
  let [s, n, o, e] = [Math.min(...lats), Math.max(...lats), Math.min(...lons), Math.max(...lons)]
  if (validos.length === 1) {
    ;[s, n, o, e] = [s - MARGEN_UN_PUNTO, n + MARGEN_UN_PUNTO, o - MARGEN_UN_PUNTO, e + MARGEN_UN_PUNTO]
  }
  return [[s, o], [n, e]]
}

export function enlaceOpenStreetMap(p: Punto): string {
  return `https://www.openstreetmap.org/?mlat=${p.lat}&mlon=${p.lon}#map=18/${p.lat}/${p.lon}`
}
