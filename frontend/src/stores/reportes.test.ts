import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import type { ReportePendiente } from '@/offline/colaReportes'

/*
 * EC-09 / M-12: si una ronda de sincronización termina con reportes pendientes estando EN LÍNEA
 * (p. ej., el servidor guardó el reporte pero la respuesta se perdió en una red 3G), la PWA debe
 * reintentar sola, con espera creciente, sin esperar a que la red se caiga y vuelva.
 * Hallazgo de la prueba de sistema S-09 (e2e/pruebas/s09-registro-sin-conexion.spec.ts).
 */
const cola = vi.hoisted(() => ({
  pendientes: [] as ReportePendiente[],
  encolar: vi.fn(),
  listar: vi.fn(),
  descartar: vi.fn(),
  sincronizar: vi.fn(),
}))
vi.mock('@/offline', () => ({ colaReportes: cola }))

import { useReportesStore } from './reportes'

const pendiente = (clave: string): ReportePendiente => ({
  claveIdempotencia: clave,
  cuerpo: { presentacionId: 'p', establecimientoId: 'e', precio: '1000', observadoEn: '2026-11-25T10:00:00Z' },
  etiqueta: { producto: 'Arroz', establecimiento: 'Tienda' },
  creadoEn: 1,
  estado: 'pendiente',
  intentos: 0,
})

describe('store de reportes: reintento automático (EC-09)', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.clearAllMocks()
    vi.stubGlobal('navigator', { onLine: true })
    vi.stubGlobal('window', { addEventListener: vi.fn() })
    setActivePinia(createPinia())
    cola.pendientes = [pendiente('a')]
    cola.listar.mockImplementation(async () => cola.pendientes)
    cola.sincronizar.mockResolvedValue({ enviados: 0, rechazados: 0, pendientes: 1 })
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
  })

  it('en línea y con pendientes tras una ronda fallida, vuelve a intentar solo a los 2 s', async () => {
    const s = useReportesStore()
    await s.sincronizar()
    expect(cola.sincronizar).toHaveBeenCalledTimes(1)

    await vi.advanceTimersByTimeAsync(1_999)
    expect(cola.sincronizar).toHaveBeenCalledTimes(1)
    await vi.advanceTimersByTimeAsync(1)
    expect(cola.sincronizar).toHaveBeenCalledTimes(2)
  })

  it('la espera se duplica en cada fallo (2 s, 4 s, 8 s) y se detiene cuando ya no quedan pendientes', async () => {
    const s = useReportesStore()
    await s.sincronizar()
    await vi.advanceTimersByTimeAsync(2_000) // 2.º intento
    await vi.advanceTimersByTimeAsync(4_000) // 3.º intento
    expect(cola.sincronizar).toHaveBeenCalledTimes(3)

    cola.pendientes = [] // el 4.º intento lo envía todo
    await vi.advanceTimersByTimeAsync(8_000)
    expect(cola.sincronizar).toHaveBeenCalledTimes(4)

    await vi.advanceTimersByTimeAsync(120_000)
    expect(cola.sincronizar).toHaveBeenCalledTimes(4)
  })

  it('la espera nunca pasa de 60 s', async () => {
    const s = useReportesStore()
    await s.sincronizar()
    for (const espera of [2_000, 4_000, 8_000, 16_000, 32_000]) await vi.advanceTimersByTimeAsync(espera)
    expect(cola.sincronizar).toHaveBeenCalledTimes(6)

    await vi.advanceTimersByTimeAsync(60_000)
    expect(cola.sincronizar).toHaveBeenCalledTimes(7)
  })

  it('sin conexión no programa reintentos: espera el evento `online`', async () => {
    vi.stubGlobal('navigator', { onLine: false })
    const s = useReportesStore()
    await s.sincronizar()

    await vi.advanceTimersByTimeAsync(120_000)
    expect(cola.sincronizar).toHaveBeenCalledTimes(1)
  })

  it('un reporte rechazado por el servidor no provoca reintentos', async () => {
    cola.pendientes = [{ ...pendiente('a'), estado: 'rechazado' }]
    const s = useReportesStore()
    await s.sincronizar()

    await vi.advanceTimersByTimeAsync(120_000)
    expect(cola.sincronizar).toHaveBeenCalledTimes(1)
  })
})
