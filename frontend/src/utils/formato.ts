import type { UnidadBase } from '@/types'

const PESOS = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0, minimumFractionDigits: 0 })
const ABREVIATURA: Record<UnidadBase, string> = { KG: 'kg', L: 'L', UNIDAD: 'und' }

const HORA_MS = 3_600_000
const DIA_MS = 24 * HORA_MS

/** RN-02: el redondeo al peso se hace SOLO al mostrar. */
export function formatearPesos(monto: string): string {
  const [entero = '0', decimales = ''] = monto.split('.')
  const redondeo = Number(`0.${decimales || '0'}`) >= 0.5 ? 1 : 0
  return `$${PESOS.format(Number(entero) + redondeo).replace(/\s/g, '')}`
}

export function precioPorUnidadTexto(precioPorUnidad: string, unidad: UnidadBase): string {
  return `${formatearPesos(precioPorUnidad)}/${ABREVIATURA[unidad]}`
}

export function formatearDistancia(km: number | undefined): string {
  if (km === undefined) return ''
  return km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(1)} km`
}

export function tiempoRelativo(iso: string, ahora: Date = new Date()): string {
  const diferencia = Math.max(0, ahora.getTime() - new Date(iso).getTime())
  if (diferencia < HORA_MS) return 'hace menos de 1 h'
  if (diferencia < DIA_MS) return `hace ${Math.floor(diferencia / HORA_MS)} h`
  const dias = Math.floor(diferencia / DIA_MS)
  return dias === 1 ? 'hace 1 día' : `hace ${dias} días`
}
