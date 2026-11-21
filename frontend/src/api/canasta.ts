import apiClient from './client'

/* POST /v1/canastas/comparacion — contrato OpenAPI 0.3.0 (ADR-0017 a ADR-0019). */
export type CriterioCanasta = 'disponibilidad' | 'total'

export interface SolicitudCanasta {
  orden: CriterioCanasta
  items: { productoId: string; cantidad: string; unidad: string }[]
  municipio?: string
}

export interface ResultadoCanastaEstablecimiento {
  posicion: number
  establecimiento: { id: string; nombre: string }
  total: string
  disponibles: number
  totalProductos: number
  completa: boolean
  faltantes: string[]
  lineas: { productoId: string; precioPorUnidad: string; cantidad: string; subtotal: string }[]
}

export interface ResultadoCanasta {
  criterioOrden: { campo: CriterioCanasta; descripcion: string }
  totalProductos: number
  generadoEn: string
  establecimientos: ResultadoCanastaEstablecimiento[]
  contenidoPromocional: unknown[]
}

export async function compararCanasta(solicitud: SolicitudCanasta): Promise<ResultadoCanasta> {
  const { data } = await apiClient.post<ResultadoCanasta>('/canastas/comparacion', solicitud)
  return data
}
