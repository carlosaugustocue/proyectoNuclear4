import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

/*
 * Sesión de moderación. La credencial vive SOLO en memoria (como la del comercio, ADR-0022):
 * al recargar hay que volver a pegarla. Llega a la consola por un canal privado, nunca por la URL.
 */
export const LONGITUD_MINIMA_CREDENCIAL = 16

export const useModeracionStore = defineStore('moderacion', () => {
  const credencial = ref<string | null>(null)
  const autenticado = computed(() => credencial.value !== null)

  function iniciarSesion(valor: string): boolean {
    const limpia = valor.trim()
    if (limpia.length < LONGITUD_MINIMA_CREDENCIAL) return false
    credencial.value = limpia
    return true
  }

  function cerrarSesion() {
    credencial.value = null
  }

  function cabeceras(): Record<string, string> {
    return credencial.value === null ? {} : { Authorization: `Bearer ${credencial.value}` }
  }

  return { autenticado, iniciarSesion, cerrarSesion, cabeceras }
})
