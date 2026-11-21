import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

/*
 * Sesión del comercio (ADR-0022). La credencial vive SOLO en memoria: no se guarda en
 * localStorage ni en sessionStorage; al recargar la página hay que volver a pegarla.
 */
export const LONGITUD_CREDENCIAL = 40

export interface EstablecimientoComercio {
  id: string
  nombre: string
}

export interface PromocionPublicada {
  promocionId: string
  descripcion: string
  producto: string
}

export const useComercioStore = defineStore('comercio', () => {
  const credencial = ref<string | null>(null)
  const establecimiento = ref<EstablecimientoComercio | null>(null)
  const promociones = ref<PromocionPublicada[]>([])
  const autenticado = computed(() => credencial.value !== null && establecimiento.value !== null)

  function iniciarSesion(valor: string, de: EstablecimientoComercio): boolean {
    const limpia = valor.trim()
    if (limpia.length !== LONGITUD_CREDENCIAL) return false
    credencial.value = limpia
    establecimiento.value = { ...de }
    return true
  }

  function cerrarSesion() {
    credencial.value = null
    establecimiento.value = null
    promociones.value = []
  }

  function cabeceras(): Record<string, string> {
    return credencial.value === null ? {} : { Authorization: `Bearer ${credencial.value}` }
  }

  function registrarPromocion(promocion: PromocionPublicada) {
    promociones.value = [{ ...promocion }, ...promociones.value]
  }

  function quitarPromocion(promocionId: string) {
    promociones.value = promociones.value.filter((p) => p.promocionId !== promocionId)
  }

  return { establecimiento, promociones, autenticado, iniciarSesion, cerrarSesion, cabeceras, registrarPromocion, quitarPromocion }
})
