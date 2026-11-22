import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { AxiosAdapter, InternalAxiosRequestConfig } from 'axios'
import apiClient, { fijarSesion } from './client'

/* ADR-0025: el cliente HTTP agrega el token de sesión (en memoria) sin pisar credenciales explícitas. */
function adaptador(respuestas: number[]): { adapter: AxiosAdapter; vistas: InternalAxiosRequestConfig[] } {
  const vistas: InternalAxiosRequestConfig[] = []
  const adapter: AxiosAdapter = async (config) => {
    vistas.push(config)
    const status = respuestas.shift() ?? 200
    const r = { data: {}, status, statusText: '', headers: {}, config }
    if (status >= 400) throw Object.assign(new Error(String(status)), { isAxiosError: true, response: r, config })
    return r
  }
  return { adapter, vistas }
}

describe('cliente HTTP con sesión', () => {
  beforeEach(() => fijarSesion(null, () => {}))

  it('sin sesión no envía Authorization', async () => {
    const { adapter, vistas } = adaptador([200])
    await apiClient.get('/productos', { adapter })
    expect(vistas[0].headers.Authorization).toBeUndefined()
  })

  it('con sesión agrega el token', async () => {
    fijarSesion('t'.repeat(64), () => {})
    const { adapter, vistas } = adaptador([200])
    await apiClient.get('/productos', { adapter })
    expect(vistas[0].headers.Authorization).toBe(`Bearer ${'t'.repeat(64)}`)
  })

  it('no pisa una credencial explícita (comercio)', async () => {
    fijarSesion('t'.repeat(64), () => {})
    const { adapter, vistas } = adaptador([200])
    await apiClient.post('/comercial/promociones', {}, { adapter, headers: { Authorization: 'Bearer credencial-comercio' } })
    expect(vistas[0].headers.Authorization).toBe('Bearer credencial-comercio')
  })

  it('si el token fue revocado (401), olvida la sesión y reintenta una vez sin él', async () => {
    const alExpirar = vi.fn()
    fijarSesion('t'.repeat(64), alExpirar)
    const { adapter, vistas } = adaptador([401, 202])

    const r = await apiClient.post('/reportes-precio', {}, { adapter })

    expect(r.status).toBe(202)
    expect(alExpirar).toHaveBeenCalledOnce()
    expect(vistas).toHaveLength(2)
    expect(vistas[1].headers.Authorization).toBeUndefined()
  })
})
