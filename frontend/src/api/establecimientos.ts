import apiClient from './client'
import type { Establecimiento, Pagina } from '@/types'

export async function buscarEstablecimientos(params: {
  q?: string
  tipo?: string
  lat?: number
  lon?: number
  radioKm?: number
  tamano?: number
} = {}) {
  const { data } = await apiClient.get<Pagina<Establecimiento>>('/establecimientos', { params })
  return data
}
