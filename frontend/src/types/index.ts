export interface Producto {
  id: string
  nombre: string
  marca: string
  presentacion: string
  categoria: string
  imagen?: string
  precioMin: string
  precioMax: string
  tiendas: number
}

export interface Establecimiento {
  id: string
  nombre: string
  tipo: 'supermercado' | 'descuento' | 'tienda_barrio' | 'minimercado'
  direccion: string
  distanciaMetros: number | null
}

export interface PrecioComparacion {
  id: string
  establecimiento: string
  tipoEstablecimiento: string
  montoDisplay: string
  precioUnidadDisplay: string
  unidadBase: string
  estadoVigencia: 'VIGENTE' | 'PUEDE_ESTAR_DESACTUALIZADO' | 'HISTORIAL'
  estadoConfianza: 'CONFIABLE' | 'POR_VERIFICAR' | 'NO_CORROBORADO' | 'NO_CONFIABLE'
  distanciaMetros: number | null
  registradoEn: string
  fuenteTipo: 'reportado' | 'oficial' | 'sipsa'
}

export interface Categoria {
  id: string
  nombre: string
  icono: string
}
