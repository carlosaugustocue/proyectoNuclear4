import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

// ADR-0025: el token de sesión vive SOLO en memoria; nunca se escribe en el almacenamiento del navegador.
const setItem = vi.fn()
const almacen = { getItem: () => null, setItem, removeItem: vi.fn(), clear: vi.fn(), key: () => null, length: 0 } as Storage
globalThis.localStorage = almacen
globalThis.sessionStorage = almacen

const api = vi.hoisted(() => ({
  registrarCuenta: vi.fn(),
  iniciarSesion: vi.fn(),
  cerrarSesion: vi.fn(),
}))
vi.mock('@/api/identidad', () => api)

const cliente = vi.hoisted(() => ({ fijarSesion: vi.fn() }))
vi.mock('@/api/client', async (original) => ({ ...(await original<object>()), ...cliente }))

import { useSesionStore } from './sesion'

const TOKEN = 'a'.repeat(64)
function respuesta(rol: 'CIUDADANO' | 'MODERADOR' = 'CIUDADANO', expiraEn = new Date(Date.now() + 8 * 3600_000).toISOString()) {
  return { cuenta: { id: 'id-1', seudonimo: 'Usuario ABCD', rol }, sesion: { token: TOKEN, expiraEn } }
}

describe('store de sesión (ADR-0025)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  it('empieza sin sesión', () => {
    const s = useSesionStore()
    expect(s.autenticado).toBe(false)
    expect(s.esModerador).toBe(false)
    expect(s.cabeceras()).toEqual({})
  })

  it('al iniciar sesión guarda la cuenta, entrega el token al cliente HTTP y no toca el almacenamiento', async () => {
    api.iniciarSesion.mockResolvedValue(respuesta())
    const s = useSesionStore()

    await s.iniciar('ana@ejemplo.test', 'una-frase-larga')

    expect(s.autenticado).toBe(true)
    expect(s.cuenta?.seudonimo).toBe('Usuario ABCD')
    expect(s.cabeceras()).toEqual({ Authorization: `Bearer ${TOKEN}` })
    expect(cliente.fijarSesion).toHaveBeenCalledWith(TOKEN, expect.any(Function))
    expect(setItem).not.toHaveBeenCalled()
  })

  it('registrar exige la autorización de datos y envía la versión de la política', async () => {
    api.registrarCuenta.mockResolvedValue(respuesta())
    const s = useSesionStore()

    await s.registrar('ana@ejemplo.test', 'una-frase-larga', true)

    expect(api.registrarCuenta).toHaveBeenCalledWith(expect.objectContaining({ aceptaTratamientoDatos: true, versionPolitica: expect.stringMatching(/^\d{4}-\d{2}$/) }))
    expect(s.autenticado).toBe(true)
  })

  it('distingue al moderador', async () => {
    api.iniciarSesion.mockResolvedValue(respuesta('MODERADOR'))
    const s = useSesionStore()
    await s.iniciar('mod@ejemplo.test', 'una-frase-larga')
    expect(s.esModerador).toBe(true)
  })

  it('una sesión vencida cuenta como cerrada', async () => {
    api.iniciarSesion.mockResolvedValue(respuesta('CIUDADANO', new Date(Date.now() - 1000).toISOString()))
    const s = useSesionStore()
    await s.iniciar('ana@ejemplo.test', 'una-frase-larga')
    expect(s.autenticado).toBe(false)
    expect(s.cabeceras()).toEqual({})
  })

  it('cerrar sesión avisa al servidor, olvida el token y lo retira del cliente HTTP', async () => {
    api.iniciarSesion.mockResolvedValue(respuesta())
    api.cerrarSesion.mockResolvedValue(undefined)
    const s = useSesionStore()
    await s.iniciar('ana@ejemplo.test', 'una-frase-larga')

    await s.cerrar()

    expect(api.cerrarSesion).toHaveBeenCalled()
    expect(s.autenticado).toBe(false)
    expect(cliente.fijarSesion).toHaveBeenLastCalledWith(null, expect.any(Function))
  })

  it('aunque el servidor no responda al cerrar, la sesión se olvida localmente', async () => {
    api.iniciarSesion.mockResolvedValue(respuesta())
    api.cerrarSesion.mockRejectedValue(new Error('sin red'))
    const s = useSesionStore()
    await s.iniciar('ana@ejemplo.test', 'una-frase-larga')

    await s.cerrar()

    expect(s.autenticado).toBe(false)
  })
})
