import apiClient from './client'
import type { Pagina } from '@/types'

/* Consola de moderación — RF-20 y RF-31 (contrato: /moderacion/*). */
type Cabeceras = Record<string, string>

export type AccionModeracion = 'CONFIRMAR_OBSERVACION' | 'INVALIDAR_OBSERVACION' | 'SUSPENDER_CUENTA' | 'CERRAR_SIN_CAMBIOS'

export interface CasoModeracion {
  id: string
  origen: 'ANOMALIA_AUTOMATICA' | 'MARCA_USUARIO' | 'CORRECCION_COMERCIO'
  estado: 'ABIERTO' | 'RESUELTO'
  detalle: string | null
  abiertoEn: string
  observacionId: string | null
  observacion: {
    precio: string
    observadoEn: string
    fuente: string
    producto: string | null
    presentacion: string | null
    establecimiento: string | null
    estadoConfianza: string | null
  } | null
}

export interface PerfilPendiente {
  perfilId: string
  estado: 'PENDIENTE' | 'VERIFICADO' | 'REVOCADO'
  solicitadoEn: string
  establecimiento: { id: string; nombre: string; direccion: string | null } | null
}

export async function listarCasos(cabeceras: Cabeceras, estado: 'ABIERTO' | 'RESUELTO' = 'ABIERTO') {
  const { data } = await apiClient.get<Pagina<CasoModeracion>>('/moderacion/casos', { headers: cabeceras, params: { estado, tamano: 50 } })
  return data
}

export async function decidirCaso(cabeceras: Cabeceras, casoId: string, accion: AccionModeracion, justificacion: string) {
  const { data } = await apiClient.post<CasoModeracion>(`/moderacion/casos/${casoId}/decision`, { accion, justificacion }, { headers: cabeceras })
  return data
}

export async function listarPerfiles(cabeceras: Cabeceras) {
  const { data } = await apiClient.get<Pagina<PerfilPendiente>>('/moderacion/perfiles', { headers: cabeceras, params: { estado: 'PENDIENTE', tamano: 50 } })
  return data
}

export async function verificarPerfil(cabeceras: Cabeceras, perfilId: string) {
  const { data } = await apiClient.post<{ perfilId: string; estado: string; credencial: string }>(
    `/moderacion/perfiles/${perfilId}/verificacion`, null, { headers: cabeceras },
  )
  return data
}
