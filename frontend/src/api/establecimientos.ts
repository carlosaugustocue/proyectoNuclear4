import apiClient from './client'
import { conTerminoValido } from './busqueda'
import type { Establecimiento, Pagina } from '@/types'

export async function buscarEstablecimientos(params: {
  q?: string
  tipo?: string
  lat?: number
  lon?: number
  radioKm?: number
  tamano?: number
} = {}) {
  const { data } = await apiClient.get<Pagina<Establecimiento>>('/establecimientos', { params: conTerminoValido(params) })
  return data
}
