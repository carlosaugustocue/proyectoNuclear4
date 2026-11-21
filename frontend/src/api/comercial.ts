import apiClient from './client'

/* Rutas del módulo Comercial — contrato OpenAPI 0.4.0 (ADR-0015, ADR-0022, ADR-0023). */
export type TipoPromocion = 'DESCUENTO_PORCENTUAL' | 'PRECIO_ESPECIAL' | 'PAGUE_X_LLEVE_Y'
type Cabeceras = Record<string, string>

export interface SolicitudPromocion {
  establecimientoId: string
  productoId: string
  tipo: TipoPromocion
  valor?: string
  pague?: number
  lleve?: number
  inicio: string
  fin: string
}

export async function solicitarPerfil(datos: { establecimientoId: string; nombreContacto: string; telefonoContacto?: string }) {
  const { data } = await apiClient.post<{ perfilId: string; estado: string }>('/comercial/perfiles', datos)
  return data
}

export async function publicarPrecioOficial(
  cabeceras: Cabeceras,
  datos: { presentacionId: string; establecimientoId: string; precio: string },
) {
  const { data } = await apiClient.post<{ observacionId: string; estado: string }>('/comercial/precios-oficiales', datos, {
    headers: { ...cabeceras, 'Idempotency-Key': crypto.randomUUID() },
  })
  return data
}

export async function publicarPromocion(cabeceras: Cabeceras, datos: SolicitudPromocion) {
  const { data } = await apiClient.post<{ promocionId: string; descripcion: string }>('/comercial/promociones', datos, { headers: cabeceras })
  return data
}

export async function retirarPromocion(cabeceras: Cabeceras, promocionId: string) {
  await apiClient.delete(`/comercial/promociones/${promocionId}`, { headers: cabeceras })
}

export async function solicitarCorreccion(cabeceras: Cabeceras, datos: { establecimientoId: string; detalle: string }) {
  const { data } = await apiClient.post<{ referencia: string; estado: string }>('/comercial/correcciones', datos, { headers: cabeceras })
  return data
}
