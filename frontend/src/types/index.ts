/*
 * Tipos alineados con docs/03-arquitectura/api/openapi.yaml (fuente de verdad del contrato).
 * Los montos llegan como string decimal exacto (RN-02): nunca se operan como float.
 */

export type UnidadBase = 'KG' | 'L' | 'UNIDAD'
export type EstadoConfianza = 'CONFIABLE' | 'NO_CORROBORADO' | 'POR_VERIFICAR' | 'NO_CONFIABLE'
export type FuenteObservacion = 'REPORTANTE' | 'COMERCIO' | 'EQUIPO_SEMILLA'
export type TipoEstablecimiento = 'TIENDA_BARRIO' | 'MINIMERCADO' | 'SUPERMERCADO' | 'DESCUENTO' | 'PLAZA'
export type OrdenComparacion = 'precio_unidad' | 'distancia'

export interface Presentacion {
  id: string
  descripcion: string | null
  cantidad: string
  unidad: 'G' | 'KG' | 'ML' | 'L' | 'UNIDAD'
  unidadesPorEmpaque: number
  unidadBase: UnidadBase
  ean: string | null
}

export interface ImagenProducto {
  url: string
  credito: string | null
}

export interface ProductoResumen {
  id: string
  nombre: string
  marca: string | null
  categoria: string
  /** Foto con licencia libre; null → ilustración de la categoría. */
  imagen?: ImagenProducto | null
}

export interface ProductoDetalle extends ProductoResumen {
  presentaciones: Presentacion[]
}

export interface Pagina<T> {
  items: T[]
  pagina: { pagina: number; tamano: number; total: number }
}

export interface Establecimiento {
  id: string
  nombre: string
  tipo: TipoEstablecimiento
  direccion: string | null
  barrio: string | null
  municipio: string
  ubicacion: { lat: number; lon: number }
  distanciaKm?: number
}

export interface ItemComparacion {
  posicion: number
  /** Id opaco de la observación, para confirmarla o marcarla (RF-10, RF-19). */
  observacionId: string
  establecimiento: {
    id: string
    nombre: string
    tipo: TipoEstablecimiento
    barrio: string | null
    distanciaKm?: number
  }
  presentacion: Presentacion
  precio: string
  precioPorUnidad: string
  observadoEn: string
  fuente: FuenteObservacion
  confirmaciones: number
  estadoConfianza: EstadoConfianza
  vigente: boolean
  advertencia: string | null
}

export interface ResultadoComparacion {
  producto: ProductoResumen
  criterioOrden: { campo: OrdenComparacion; descripcion: string }
  unidadBase: UnidadBase
  generadoEn: string
  items: ItemComparacion[]
  contenidoPromocional: PromocionPublica[]
}

/** Promoción visible (RN-16): va aparte del resultado y nunca cambia el orden ni el total (ADR-0023). */
export interface PromocionPublica {
  tipo: 'DESCUENTO_PORCENTUAL' | 'PRECIO_ESPECIAL' | 'PAGUE_X_LLEVE_Y'
  descripcion: string
  establecimiento: { id: string; nombre: string }
  productoId: string
  vigenteHasta: string
  esPromocion: true
}

export interface ResumenProducto {
  productoId: string
  unidadBase: UnidadBase | null
  precioPorUnidadMin: string | null
  precioMin: string | null
  establecimientos: number
}

export interface ReportePrecioNuevo {
  presentacionId: string
  establecimientoId: string
  precio: string
  observadoEn: string
}

export interface ReporteAceptado {
  observacionId: string
  estado: 'PENDIENTE_EVALUACION'
}
