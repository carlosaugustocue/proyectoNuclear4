import { ref } from 'vue'
import { defineStore } from 'pinia'
import { registrarPrecio, type RegistroPrecioPayload } from '@/api/precios'

export const useRegistroStore = defineStore('registro', () => {
  const cargando = ref(false)
  const exito = ref(false)
  const error = ref<string | null>(null)

  async function enviar(payload: RegistroPrecioPayload) {
    cargando.value = true
    exito.value = false
    error.value = null

    try {
      await registrarPrecio(payload)
      exito.value = true
    } catch (err: unknown) {
      if (err instanceof Error) {
        error.value = err.message
      } else {
        error.value = 'Error al registrar el precio'
      }
      // TODO (EC-09): guardar en IndexedDB para sync posterior
    } finally {
      cargando.value = false
    }
  }

  function reset() {
    cargando.value = false
    exito.value = false
    error.value = null
  }

  return { cargando, exito, error, enviar, reset }
})
