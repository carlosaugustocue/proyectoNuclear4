import type { ItemComparacion, Presentacion, TipoEstablecimiento } from '@/types'

/** Categorías del catálogo (coinciden con `productos.categoria`). `null` = todas. */
export const CATEGORIAS: { nombre: string; valor: string | null }[] = [
  { nombre: 'Todos', valor: null },
  { nombre: 'Granos', valor: 'Granos' },
  { nombre: 'Lácteos', valor: 'Lácteos' },
  { nombre: 'Aceites', valor: 'Aceites' },
  { nombre: 'Bebidas', valor: 'Bebidas' },
  { nombre: 'Frutas y verduras', valor: 'Frutas y verduras' },
  { nombre: 'Carnes', valor: 'Carnes' },
  { nombre: 'Pescados', valor: 'Pescados' },
  { nombre: 'Despensa', valor: 'Despensa' },
  { nombre: 'Aseo', valor: 'Aseo' },
]

export const TIPO_ESTABLECIMIENTO: Record<TipoEstablecimiento, string> = {
  TIENDA_BARRIO: 'Tienda de barrio',
  MINIMERCADO: 'Minimercado',
  SUPERMERCADO: 'Supermercado',
  DESCUENTO: 'Tienda de descuento',
  PLAZA: 'Plaza de mercado',
}

export function describirPresentacion(p: Presentacion): string {
  return p.descripcion ?? `${p.unidadesPorEmpaque > 1 ? `${p.unidadesPorEmpaque} × ` : ''}${p.cantidad} ${p.unidad}`
}

export type EstadoVigencia = 'VIGENTE' | 'PUEDE_ESTAR_DESACTUALIZADO' | 'HISTORIAL'

/** Traduce la respuesta de la API (vigente + advertencia, RN-04) a la insignia de vigencia. */
export function estadoVigencia(item: Pick<ItemComparacion, 'vigente' | 'advertencia'>): EstadoVigencia {
  if (!item.vigente) return 'HISTORIAL'
  return item.advertencia?.includes('desactualizado') ? 'PUEDE_ESTAR_DESACTUALIZADO' : 'VIGENTE'
}
