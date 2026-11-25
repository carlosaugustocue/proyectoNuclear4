/* Imagen del producto: foto con licencia libre (si existe) o ilustración de su categoría. */
export type IconoCategoria = 'granos' | 'lacteos' | 'aceites' | 'bebidas' | 'frutas' | 'aseo' | 'carnes' | 'pescados' | 'despensa' | 'generico'

export interface Ilustracion {
  icono: IconoCategoria
  /** Clases de fondo y color del ícono (paleta semántica por categoría). */
  clases: string
}

const ILUSTRACIONES: Record<string, Ilustracion> = {
  granos: { icono: 'granos', clases: 'bg-amber-50 text-amber-600' },
  lacteos: { icono: 'lacteos', clases: 'bg-sky-50 text-sky-600' },
  aceites: { icono: 'aceites', clases: 'bg-yellow-50 text-yellow-600' },
  bebidas: { icono: 'bebidas', clases: 'bg-rose-50 text-rose-600' },
  'frutas y verduras': { icono: 'frutas', clases: 'bg-emerald-50 text-emerald-600' },
  aseo: { icono: 'aseo', clases: 'bg-violet-50 text-violet-600' },
  carnes: { icono: 'carnes', clases: 'bg-red-50 text-red-700' },
  pescados: { icono: 'pescados', clases: 'bg-cyan-50 text-cyan-700' },
  despensa: { icono: 'despensa', clases: 'bg-orange-50 text-orange-700' },
}
const GENERICA: Ilustracion = { icono: 'generico', clases: 'bg-slate-100 text-slate-500' }

function normalizar(texto: string): string {
  return texto.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim()
}

export function ilustracionDe(categoria: string): Ilustracion {
  return ILUSTRACIONES[normalizar(categoria)] ?? GENERICA
}

export function urlSegura(url: string | null | undefined): string | null {
  return typeof url === 'string' && url.startsWith('https://') ? url : null
}
