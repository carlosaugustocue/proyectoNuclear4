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

/*
 * Sesión de la cuenta (ADR-0025). El token vive SOLO en esta variable de módulo (memoria): nunca en
 * localStorage ni sessionStorage. Si la API lo rechaza (401: vencido, revocado o cuenta suspendida),
 * se olvida y la petición se repite UNA vez sin él; así un reporte en cola no se pierde (va anónimo).
 */
let tokenSesion: string | null = null
let alExpirarSesion: () => void = () => {}

declare module 'axios' {
  interface InternalAxiosRequestConfig {
    /** Marca interna (no viaja al servidor): el Authorization lo puso la sesión, no el llamador. */
    conSesion?: boolean
  }
}

export function fijarSesion(token: string | null, alExpirar: () => void): void {
  tokenSesion = token
  alExpirarSesion = alExpirar
}

apiClient.interceptors.request.use((config) => {
  if (tokenSesion !== null && !config.headers.Authorization) {
    config.headers.Authorization = `Bearer ${tokenSesion}`
    config.conSesion = true
  }
  return config
})

apiClient.interceptors.response.use(undefined, async (error) => {
  const config = isAxiosError(error) ? error.config : undefined
  if (config?.conSesion && error.response?.status === 401) {
    tokenSesion = null
    alExpirarSesion()
    delete config.headers.Authorization
    config.conSesion = false
    return apiClient.request(config)
  }
  return Promise.reject(error)
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

/** Todos los mensajes de validación de un Problem Details (p. ej., los de RN-14 juntos). */
export function todosLosErrores(error: unknown): string[] {
  if (isAxiosError<Problema>(error) && error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat()
  }
  return [mensajeDeError(error)]
}

/** true si el error se debe a falta de red (no a una respuesta del servidor). */
export function esFallaDeRed(error: unknown): boolean {
  return isAxiosError(error) && !error.response
}

export default apiClient
