import { precioPorUnidadTexto } from './formato'
import type { UnidadBase } from '@/types'

interface ItemConPrecio {
  observacionId: string
  establecimiento: { nombre: string }
  precioPorUnidad: string
  vigente: boolean
}

export interface PuntoRango {
  id: string
  nombre: string
  /** 0 = la más barata, 1 = la más cara; 0,5 si todas valen lo mismo. */
  posicion: number
}

export interface RangoPrecios {
  minimo: string
  maximo: string
  /** Cuánto más cuesta la más cara que la más barata, en % entero. */
  diferenciaPorcentaje: number
  tiendas: number
  puntos: PuntoRango[]
}

/** Franja de precios vigentes por unidad (RN-10): solo describe el criterio declarado, no reordena. */
export function rangoDePrecios(items: readonly ItemConPrecio[], unidad: UnidadBase): RangoPrecios | null {
  const vigentes = items.filter((i) => i.vigente)
  if (vigentes.length === 0) return null

  const valores = vigentes.map((i) => Number(i.precioPorUnidad))
  const min = Math.min(...valores)
  const max = Math.max(...valores)
  const amplitud = max - min

  return {
    minimo: precioPorUnidadTexto(String(min), unidad),
    maximo: precioPorUnidadTexto(String(max), unidad),
    diferenciaPorcentaje: min > 0 ? Math.round((amplitud / min) * 100) : 0,
    tiendas: vigentes.length,
    puntos: vigentes.map((i, k) => ({
      id: i.observacionId,
      nombre: i.establecimiento.nombre,
      posicion: amplitud === 0 ? 0.5 : (valores[k]! - min) / amplitud,
    })),
  }
}
