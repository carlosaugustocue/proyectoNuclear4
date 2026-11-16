import { isAxiosError } from 'axios'
import apiClient from './client'
import type { ReporteAceptado, ReportePrecioNuevo } from '@/types'
import type { ResultadoEnvio } from '@/offline/colaReportes'

export async function enviarReporte(cuerpo: ReportePrecioNuevo, claveIdempotencia: string) {
  const { data } = await apiClient.post<ReporteAceptado>('/reportes-precio', cuerpo, {
    headers: { 'Idempotency-Key': claveIdempotencia },
  })
  return data
}

/**
 * Adaptador para la cola offline: distingue "sin conexión / servidor caído" (se
 * reintenta) de "rechazado por validación" (no se reintenta).
 */
export async function enviarDesdeCola(cuerpo: ReportePrecioNuevo, clave: string): Promise<ResultadoEnvio> {
  try {
    await enviarReporte(cuerpo, clave)
    return 'aceptado'
  } catch (error) {
    if (!isAxiosError(error) || !error.response) return 'sin-conexion'
    const { status } = error.response
    return status === 429 || status >= 500 ? 'sin-conexion' : 'rechazado'
  }
}
