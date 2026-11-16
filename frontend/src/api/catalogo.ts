import apiClient from './client'
import type { Pagina, ProductoDetalle, ProductoResumen } from '@/types'

export async function buscarProductos(params: { q?: string; categoria?: string; tamano?: number } = {}) {
  const { data } = await apiClient.get<Pagina<ProductoResumen>>('/productos', { params })
  return data
}

export async function obtenerProducto(productoId: string) {
  const { data } = await apiClient.get<ProductoDetalle>(`/productos/${productoId}`)
  return data
}
