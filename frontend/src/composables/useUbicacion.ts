import { ref } from 'vue'

/*
 * Ubicación del dispositivo SOLO en memoria y solo cuando el usuario la pide
 * (privacidad por diseño, Ley 1581/2012): nunca se guarda ni se envía con un reporte.
 */
export function useUbicacion() {
  const coordenadas = ref<{ lat: number; lon: number } | null>(null)
  const estado = ref<'inactiva' | 'buscando' | 'lista' | 'denegada' | 'no-disponible'>('inactiva')

  function solicitar(): Promise<void> {
    if (!('geolocation' in navigator)) {
      estado.value = 'no-disponible'
      return Promise.resolve()
    }
    estado.value = 'buscando'
    return new Promise((resolver) => {
      navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
          coordenadas.value = { lat: coords.latitude, lon: coords.longitude }
          estado.value = 'lista'
          resolver()
        },
        () => {
          estado.value = 'denegada'
          resolver()
        },
        { enableHighAccuracy: false, timeout: 10_000, maximumAge: 300_000 },
      )
    })
  }

  return { coordenadas, estado, solicitar }
}
