import axios, { isAxiosError } from 'axios'

/*
 * El contrato publica la API en /v1 (openapi.yaml → servers). En desarrollo Vite
 * hace de proxy hacia el backend; en producción nginx/Railway enruta /v1 a la API.
 */
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/v1',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  timeout: 15_000,
})

/** Problem Details (RFC 9457) que devuelve la API en todos los errores. */
export interface Problema {
  title: string
  status: number
  detail?: string
  errors?: Record<string, string[]>
}

export function mensajeDeError(error: unknown, porDefecto = 'No pudimos conectar con el servidor.'): string {
  if (isAxiosError<Problema>(error) && error.response?.data) {
    const { detail, title, errors } = error.response.data
    const primero = errors ? Object.values(errors)[0]?.[0] : undefined
    return primero ?? detail ?? title ?? porDefecto
  }
  return porDefecto
}

/** true si el error se debe a falta de red (no a una respuesta del servidor). */
export function esFallaDeRed(error: unknown): boolean {
  return isAxiosError(error) && !error.response
}

export default apiClient
