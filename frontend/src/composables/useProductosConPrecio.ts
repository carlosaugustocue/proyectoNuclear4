import { ref } from 'vue'
import { buscarProductos } from '@/api/catalogo'
import { resumirPrecios } from '@/api/comparaciones'
import { mensajeDeError } from '@/api/client'
import type { ProductoResumen, ResumenProducto } from '@/types'

/** Busca productos en el catálogo y los acompaña con su menor precio vigente. */
export function useProductosConPrecio() {
  const productos = ref<ProductoResumen[]>([])
  const resumenes = ref<Record<string, ResumenProducto>>({})
  const total = ref(0)
  const cargando = ref(false)
  const error = ref<string | null>(null)
  let consultaActual = 0

  async function cargar(params: { q?: string; categoria?: string; tamano?: number }) {
    const consulta = ++consultaActual
    cargando.value = true
    error.value = null
    try {
      const pagina = await buscarProductos(params)
      const resumen = await resumirPrecios(pagina.items.map((p) => p.id))
      if (consulta !== consultaActual) return // llegó una búsqueda más reciente
      productos.value = pagina.items
      total.value = pagina.pagina.total
      resumenes.value = Object.fromEntries(resumen.map((r) => [r.productoId, r]))
    } catch (e) {
      if (consulta === consultaActual) error.value = mensajeDeError(e)
    } finally {
      if (consulta === consultaActual) cargando.value = false
    }
  }

  return { productos, resumenes, total, cargando, error, cargar }
}
