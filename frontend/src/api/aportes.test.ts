import { describe, expect, it } from 'vitest'
import type { AxiosAdapter, InternalAxiosRequestConfig } from 'axios'
import apiClient from './client'
import { confirmarPrecio, marcarPrecio, nuevaClaveConfirmacion } from './aportes'

/* RF-10 y RF-19 (ADR-0026): lo que la PWA envía al confirmar y al marcar. */
function capturar(): InternalAxiosRequestConfig[] {
  const vistas: InternalAxiosRequestConfig[] = []
  const adapter: AxiosAdapter = async (config) => {
    vistas.push(config)
    return { data: { observacionId: 'nueva' }, status: 202, statusText: '', headers: {}, config }
  }
  apiClient.defaults.adapter = adapter
  return vistas
}

describe('aportes', () => {
  it('la clave de confirmación es un UUID nuevo cada vez', () => {
    const a = nuevaClaveConfirmacion()
    expect(a).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/)
    expect(nuevaClaveConfirmacion()).not.toBe(a)
  })

  it('confirmar envía la Idempotency-Key dada: un reintento reutiliza la misma (EC-09)', async () => {
    const vistas = capturar()
    const clave = nuevaClaveConfirmacion()

    await confirmarPrecio('obs-1', clave)
    await confirmarPrecio('obs-1', clave)

    expect(vistas.map((v) => v.url)).toEqual(['/observaciones/obs-1/confirmaciones', '/observaciones/obs-1/confirmaciones'])
    expect(vistas[0].headers['Idempotency-Key']).toBe(clave)
    expect(vistas[1].headers['Idempotency-Key']).toBe(clave)
  })

  it('marcar envía el motivo y omite un comentario vacío', async () => {
    const vistas = capturar()

    await marcarPrecio('obs-1', 'DESACTUALIZADO', '   ')
    await marcarPrecio('obs-1', 'OTRO', '  Ya no lo venden  ')

    expect(JSON.parse(vistas[0].data)).toEqual({ motivo: 'DESACTUALIZADO' })
    expect(JSON.parse(vistas[1].data)).toEqual({ motivo: 'OTRO', comentario: 'Ya no lo venden' })
  })
})
