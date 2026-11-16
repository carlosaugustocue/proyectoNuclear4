import apiClient from './client'
import type { OrdenComparacion, ResultadoComparacion, ResumenProducto } from '@/types'

export interface ParametrosComparacion {
  productoId: string
  presentacionId?: string
  orden?: OrdenComparacion
  lat?: number
  lon?: number
  radioKm?: number
  incluirNoVigentes?: boolean
}

export async function compararPrecios(params: ParametrosComparacion) {
  const { data } = await apiClient.get<ResultadoComparacion>('/comparaciones', { params })
  return data
}

export async function resumirPrecios(productoIds: string[]): Promise<ResumenProducto[]> {
  if (productoIds.length === 0) return []
  const { data } = await apiClient.get<{ items: ResumenProducto[] }>('/comparaciones/resumen', {
    params: { productoIds: productoIds.join(',') },
  })
  return data.items
}
