import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ColaReportes, crearBaseCola, type Enviador } from './colaReportes'
import type { ReportePrecioNuevo } from '@/types'

/*
 * EC-09 / M-12: los reportes hechos sin conexión se guardan en IndexedDB y se
 * sincronizan al volver la red, con la MISMA Idempotency-Key en cada reintento
 * para que el servidor nunca los duplique.
 */

const cuerpo = (precio = '2990'): ReportePrecioNuevo => ({
  presentacionId: '0193a1d2-0000-7000-8000-000000000001',
  establecimientoId: '0193a1d2-0000-7000-8000-000000000002',
  precio,
  observadoEn: new Date().toISOString(),
})
const etiqueta = { producto: 'Arroz 500 g', establecimiento: 'Tienda Doña Rosa' }

let nombreBase = 0
function nuevaCola(enviar: Enviador) {
  return new ColaReportes(crearBaseCola(`cola-test-${++nombreBase}`), enviar)
}

describe('ColaReportes (EC-09)', () => {
  let enviar: ReturnType<typeof vi.fn<Enviador>>

  beforeEach(() => {
    enviar = vi.fn<Enviador>()
  })

  it('encola el reporte con una clave de idempotencia UUID', async () => {
    const cola = nuevaCola(enviar)
    const pendiente = await cola.encolar(cuerpo(), etiqueta)

    expect(pendiente.claveIdempotencia).toMatch(/^[0-9a-f-]{36}$/)
    expect(await cola.contarPendientes()).toBe(1)
  })

  it('sincroniza en orden de llegada y elimina los aceptados', async () => {
    enviar.mockResolvedValue('aceptado')
    const cola = nuevaCola(enviar)
    await cola.encolar(cuerpo('1000'), etiqueta)
    await cola.encolar(cuerpo('2000'), etiqueta)

    const resultado = await cola.sincronizar()

    expect(resultado).toEqual({ enviados: 2, rechazados: 0, pendientes: 0 })
    expect(enviar.mock.calls.map(([c]) => c.precio)).toEqual(['1000', '2000'])
  })

  it('sin conexión conserva el reporte y lo reintenta con la misma clave (sin duplicar)', async () => {
    enviar.mockResolvedValueOnce('sin-conexion').mockResolvedValueOnce('aceptado')
    const cola = nuevaCola(enviar)
    const { claveIdempotencia } = await cola.encolar(cuerpo(), etiqueta)

    expect(await cola.sincronizar()).toEqual({ enviados: 0, rechazados: 0, pendientes: 1 })
    expect(await cola.sincronizar()).toEqual({ enviados: 1, rechazados: 0, pendientes: 0 })
    expect(enviar.mock.calls.map(([, clave]) => clave)).toEqual([claveIdempotencia, claveIdempotencia])
  })

  it('deja de intentar en la misma ronda cuando no hay conexión', async () => {
    enviar.mockResolvedValue('sin-conexion')
    const cola = nuevaCola(enviar)
    await cola.encolar(cuerpo('1'), etiqueta)
    await cola.encolar(cuerpo('2'), etiqueta)
    await cola.encolar(cuerpo('3'), etiqueta)

    await cola.sincronizar()

    expect(enviar).toHaveBeenCalledTimes(1)
  })

  it('un reporte rechazado por el servidor (422) no se reintenta y queda visible con su error', async () => {
    enviar.mockResolvedValue('rechazado')
    const cola = nuevaCola(enviar)
    await cola.encolar(cuerpo(), etiqueta)

    expect(await cola.sincronizar()).toEqual({ enviados: 0, rechazados: 1, pendientes: 0 })
    await cola.sincronizar()

    expect(enviar).toHaveBeenCalledTimes(1)
    const [rechazado] = await cola.listar()
    expect(rechazado?.estado).toBe('rechazado')
  })

  it('tres reportes offline se sincronizan todos, sin duplicados (M-12 = 1)', async () => {
    const recibidas = new Set<string>()
    enviar.mockImplementation(async (_c, clave) => {
      recibidas.add(clave)
      return 'aceptado'
    })
    const cola = nuevaCola(enviar)
    for (const p of ['1000', '2000', '3000']) await cola.encolar(cuerpo(p), etiqueta)

    await cola.sincronizar()
    await cola.sincronizar()

    expect(recibidas.size).toBe(3)
    expect(enviar).toHaveBeenCalledTimes(3)
  })
})
