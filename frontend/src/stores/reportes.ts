import { ref } from 'vue'
import { defineStore } from 'pinia'
import { colaReportes } from '@/offline'
import type { ReportePendiente } from '@/offline/colaReportes'
import type { ReportePrecioNuevo } from '@/types'

export type DesenlaceReporte = 'enviado' | 'guardado-offline' | 'rechazado'

/*
 * Estado cliente de los reportes. Todo reporte pasa primero por la cola (EC-09):
 * si hay red se envía en el acto; si no, queda en IndexedDB y se sincroniza solo
 * cuando el navegador recupera la conexión (evento `online`).
 */
export const useReportesStore = defineStore('reportes', () => {
  const pendientes = ref<ReportePendiente[]>([])
  const sincronizando = ref(false)
  const enLinea = ref(typeof navigator === 'undefined' ? true : navigator.onLine)
  let iniciado = false

  async function refrescar() {
    pendientes.value = await colaReportes.listar()
  }

  async function sincronizar() {
    sincronizando.value = true
    try {
      await colaReportes.sincronizar()
    } finally {
      sincronizando.value = false
      await refrescar()
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
      void sincronizar()
    })
    window.addEventListener('offline', () => {
      enLinea.value = false
    })
    void (enLinea.value ? sincronizar() : refrescar())
  }

  return { pendientes, sincronizando, enLinea, iniciar, reportar, sincronizar, descartar }
})
