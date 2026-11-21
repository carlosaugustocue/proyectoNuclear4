import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import type { CriterioCanasta, SolicitudCanasta } from '@/api/canasta'

/*
 * Canasta del consumidor (RF-27). Vive solo en este dispositivo y no requiere cuenta
 * (ADR-0018): el servidor nunca la guarda; se envía completa en cada comparación.
 */
export const MAX_PRODUCTOS = 20
const CLAVE = 'canasta-v1'

const UNIDADES: Record<string, string[]> = { KG: ['g', 'kg'], L: ['mL', 'L'], UNIDAD: ['unidad'] }

export interface ItemCanastaLocal {
  productoId: string
  nombre: string
  unidadBase: string
  cantidad: string
  unidad: string
}

export function unidadesPermitidas(unidadBase: string): string[] {
  return UNIDADES[unidadBase] ?? ['unidad']
}

function unidadPorDefecto(unidadBase: string): string {
  const opciones = unidadesPermitidas(unidadBase)
  return opciones[opciones.length - 1]
}

function cargar(): ItemCanastaLocal[] {
  try {
    const guardado = localStorage.getItem(CLAVE)
    const datos: unknown = guardado ? JSON.parse(guardado) : []
    return Array.isArray(datos) ? (datos as ItemCanastaLocal[]).slice(0, MAX_PRODUCTOS) : []
  } catch {
    return [] // almacenamiento bloqueado o corrupto: se empieza con la canasta vacía
  }
}

export const useCanastaStore = defineStore('canasta', () => {
  const items = ref<ItemCanastaLocal[]>(cargar())
  const criterio = ref<CriterioCanasta>('disponibilidad')
  const llena = computed(() => items.value.length >= MAX_PRODUCTOS)

  watch(
    items,
    (valor) => {
      try {
        localStorage.setItem(CLAVE, JSON.stringify(valor))
      } catch {
        /* sin almacenamiento disponible: la canasta dura lo que dure la pestaña */
      }
    },
    { deep: true },
  )

  function agregar(producto: { productoId: string; nombre: string; unidadBase: string }): boolean {
    if (llena.value || items.value.some((i) => i.productoId === producto.productoId)) return false
    items.value = [...items.value, { ...producto, cantidad: '1', unidad: unidadPorDefecto(producto.unidadBase) }]
    return true
  }

  function actualizar(productoId: string, cambios: Partial<Pick<ItemCanastaLocal, 'cantidad' | 'unidad'>>) {
    items.value = items.value.map((i) => (i.productoId === productoId ? { ...i, ...cambios } : i))
  }

  function quitar(productoId: string) {
    items.value = items.value.filter((i) => i.productoId !== productoId)
  }

  function vaciar() {
    items.value = []
  }

  function solicitud(): SolicitudCanasta {
    return {
      orden: criterio.value,
      items: items.value.map(({ productoId, cantidad, unidad }) => ({ productoId, cantidad, unidad })),
    }
  }

  return { items, criterio, llena, agregar, actualizar, quitar, vaciar, solicitud }
})
