import { ref } from 'vue'
import { defineStore } from 'pinia'
import { compararPrecios, type PrecioComparacion } from '@/api/precios'

export const useComparacionStore = defineStore('comparacion', () => {
  const resultados = ref<PrecioComparacion[]>([])
  const criterioOrden = ref<string>('precio')
  const cargando = ref(false)
  const error = ref<string | null>(null)

  async function buscar(productoId: string, orden: 'precio' | 'distancia' = 'precio') {
    cargando.value = true
    error.value = null

    try {
      const { data } = await compararPrecios(productoId, { orden })
      resultados.value = data.data
      criterioOrden.value = data.criterio_orden
    } catch (err: unknown) {
      if (err instanceof Error) {
        error.value = err.message
      } else {
        error.value = 'Error al consultar precios'
      }
      resultados.value = []
    } finally {
      cargando.value = false
    }
  }

  return { resultados, criterioOrden, cargando, error, buscar }
})
