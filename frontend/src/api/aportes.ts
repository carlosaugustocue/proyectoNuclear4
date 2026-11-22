import apiClient from './client'
import type { EstadoConfianza } from '@/types'

/* Aportes con cuenta — RF-10, RF-12, RF-19 (ADR-0026). Requieren sesión (la agrega el cliente HTTP). */
export type MotivoMarca = 'PRECIO_INCORRECTO' | 'PRODUCTO_EQUIVOCADO' | 'ESTABLECIMIENTO_EQUIVOCADO' | 'DESACTUALIZADO' | 'OTRO'

export const MOTIVOS_MARCA: { valor: MotivoMarca; texto: string }[] = [
  { valor: 'PRECIO_INCORRECTO', texto: 'El precio no es ese' },
  { valor: 'DESACTUALIZADO', texto: 'Está desactualizado' },
  { valor: 'PRODUCTO_EQUIVOCADO', texto: 'No es ese producto' },
  { valor: 'ESTABLECIMIENTO_EQUIVOCADO', texto: 'No es esa tienda' },
  { valor: 'OTRO', texto: 'Otro motivo' },
]

export interface MiAporte {
  observacionId: string
  producto: string | null
  presentacion: string | null
  establecimiento: string | null
  precio: string
  observadoEn: string
  estadoConfianza: EstadoConfianza | 'PENDIENTE_EVALUACION'
}

export interface MisAportes {
  items: MiAporte[]
  pagina: { pagina: number; tamano: number; total: number }
  reputacion: { aportesConfirmados: number; requiereCorroboracion: boolean }
}

/** Una clave por intención de confirmar: si la red falla y se reintenta, se reutiliza (EC-09). */
export function nuevaClaveConfirmacion(): string {
  return crypto.randomUUID()
}

export async function confirmarPrecio(observacionId: string, clave: string): Promise<{ observacionId: string }> {
  const { data } = await apiClient.post<{ observacionId: string }>(`/observaciones/${observacionId}/confirmaciones`, null, {
    headers: { 'Idempotency-Key': clave },
  })
  return data
}

export async function marcarPrecio(observacionId: string, motivo: MotivoMarca, comentario: string): Promise<void> {
  const limpio = comentario.trim()
  await apiClient.post(`/observaciones/${observacionId}/marcas`, limpio === '' ? { motivo } : { motivo, comentario: limpio })
}

export async function misAportes(pagina = 1): Promise<MisAportes> {
  const { data } = await apiClient.get<MisAportes>('/mis-aportes', { params: { pagina, tamano: 20 } })
  return data
}
