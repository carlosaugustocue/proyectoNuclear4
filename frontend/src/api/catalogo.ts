import apiClient from './client'
import type { Pagina, ProductoDetalle, ProductoResumen, ReferenciaMayorista } from '@/types'

export async function buscarProductos(params: { q?: string; categoria?: string; tamano?: number } = {}) {
  const { data } = await apiClient.get<Pagina<ProductoResumen>>('/productos', { params })
  return data
}

export async function obtenerProducto(productoId: string) {
  const { data } = await apiClient.get<ProductoDetalle>(`/productos/${productoId}`)
  return data
}

/** Referencia mayorista SIPSA-DANE del producto (RF-25): aparte de la comparación (ADR-0010). */
export async function obtenerReferenciaMayorista(productoId: string) {
  const { data } = await apiClient.get<ReferenciaMayorista>(`/productos/${productoId}/referencia-mayorista`)
  return data
}
