import apiClient from './client'

export interface RegistroPrecioPayload {
  producto_id: string
  establecimiento_id: string
  monto: string
  moneda?: string
  latitud: number
  longitud: number
}

export interface PrecioComparacion {
  id: string
  producto: string
  establecimiento: string
  monto_display: string
  precio_unidad_display: string
  unidad_base: string
  estado_vigencia: string
  estado_confianza: string
  distancia_metros: number | null
  registrado_en: string
}

export interface ComparacionResponse {
  data: PrecioComparacion[]
  criterio_orden: string
}

export function registrarPrecio(payload: RegistroPrecioPayload) {
  return apiClient.post('/precios', payload)
}

export function compararPrecios(productoId: string, params?: {
  lat?: number
  lon?: number
  orden?: 'precio' | 'distancia'
}) {
  return apiClient.get<ComparacionResponse>(`/comparacion/${productoId}`, { params })
}
