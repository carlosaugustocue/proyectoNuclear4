import { ref } from 'vue'
import { defineStore } from 'pinia'
import { colaReportes } from '@/offline'
import type { ReportePendiente } from '@/offline/colaReportes'
import type { ReportePrecioNuevo } from '@/types'

export type DesenlaceReporte = 'enviado' | 'guardado-offline' | 'rechazado'

/** Espera antes de reintentar una ronda que dejó pendientes con red: 2 s, 4 s, 8 s… hasta 60 s. */
const REINTENTO_INICIAL_MS = 2_000
const REINTENTO_MAXIMO_MS = 60_000

const navegadorEnLinea = () => (typeof navigator === 'undefined' ? true : navigator.onLine !== false)

/*
 * Estado cliente de los reportes. Todo reporte pasa primero por la cola (EC-09):
 * si hay red se envía en el acto; si no, queda en IndexedDB y se sincroniza solo
 * cuando el navegador recupera la conexión (evento `online`).
 *
 * Si una ronda termina con pendientes estando en línea (la respuesta se perdió, el servidor
 * respondió 5xx o 429), se reintenta sola con espera exponencial: en redes 3G el evento
 * `online` no vuelve a dispararse y la cola quedaría trabada (hallazgo de S-09).
 */
export const useReportesStore = defineStore('reportes', () => {
  const pendientes = ref<ReportePendiente[]>([])
  const sincronizando = ref(false)
  const enLinea = ref(navegadorEnLinea())
  let iniciado = false
  let reintento: ReturnType<typeof setTimeout> | undefined
  let fallosSeguidos = 0

  async function refrescar() {
    pendientes.value = await colaReportes.listar()
  }

  function cancelarReintento() {
    clearTimeout(reintento)
    reintento = undefined
  }

  function programarReintento() {
    cancelarReintento()
    const quedanPendientes = pendientes.value.some((r) => r.estado === 'pendiente')
    if (!quedanPendientes || !navegadorEnLinea()) {
      fallosSeguidos = 0
      return
    }
    const espera = Math.min(REINTENTO_INICIAL_MS * 2 ** fallosSeguidos, REINTENTO_MAXIMO_MS)
    fallosSeguidos++
    reintento = setTimeout(() => void sincronizar(), espera)
  }

  async function sincronizar() {
    sincronizando.value = true
    try {
      await colaReportes.sincronizar()
    } finally {
      sincronizando.value = false
      await refrescar()
      programarReintento()
    }
  }

  async function descartar(claveIdempotencia: string) {
    await colaReportes.descartar(claveIdempotencia)
    await refrescar()
  }

  async function reportar(
    cuerpo: ReportePrecioNuevo,
    etiqueta: ReportePendiente['etiqueta'],
  ): Promise<DesenlaceReporte> {
    const { claveIdempotencia } = await colaReportes.encolar(cuerpo, etiqueta)
    await sincronizar()
    const propio = pendientes.value.find((r) => r.claveIdempotencia === claveIdempotencia)
    if (!propio) return 'enviado'
    if (propio.estado === 'rechazado') {
      await descartar(claveIdempotencia)
      return 'rechazado'
    }
    return 'guardado-offline'
  }

  function iniciar() {
    if (iniciado) return
    iniciado = true
    window.addEventListener('online', () => {
      enLinea.value = true
      fallosSeguidos = 0
      void sincronizar()
    })
    window.addEventListener('offline', () => {
      enLinea.value = false
      cancelarReintento()
    })
    void (enLinea.value ? sincronizar() : refrescar())
  }

  return { pendientes, sincronizando, enLinea, iniciar, reportar, sincronizar, descartar }
})
