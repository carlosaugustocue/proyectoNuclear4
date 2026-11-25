import type { ReferenciaMayorista } from '@/types'
import { precioPorUnidadTexto } from './formato'

export interface ResumenReferencia {
  precio: string
  mes: string
  variacion: { porcentaje: number; sentido: 'sube' | 'baja' | 'igual' } | null
  /** Altura relativa de cada mes (0 = mínimo, 1 = máximo) para la mini gráfica. */
  puntos: number[]
}

const MES = new Intl.DateTimeFormat('es-CO', { month: 'long', year: 'numeric', timeZone: 'UTC' })

/** Resume la serie mensual SIPSA para mostrarla junto a la comparación, sin mezclarla con ella (ADR-0010). */
export function resumirReferencia(referencia: ReferenciaMayorista): ResumenReferencia | null {
  const { serie } = referencia
  const ultimo = serie.at(-1)
  if (!ultimo) return null

  const precios = serie.map((p) => Number(p.precio))
  const min = Math.min(...precios)
  const max = Math.max(...precios)
  const anterior = precios.at(-2)
  const actual = precios.at(-1)!

  let variacion: ResumenReferencia['variacion'] = null
  if (anterior !== undefined && anterior > 0) {
    const porcentaje = Math.round(((actual - anterior) / anterior) * 100)
    variacion = { porcentaje, sentido: porcentaje > 0 ? 'sube' : porcentaje < 0 ? 'baja' : 'igual' }
  }

  return {
    precio: precioPorUnidadTexto(ultimo.precio, referencia.unidadBase ?? 'KG'),
    mes: MES.format(new Date(`${ultimo.mes}-01T00:00:00Z`)),
    variacion,
    puntos: precios.map((p) => (max === min ? 0.5 : (p - min) / (max - min))),
  }
}
