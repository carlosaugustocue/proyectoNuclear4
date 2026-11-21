import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useModeracionStore } from './moderacion'

// Espías de almacenamiento: la credencial de moderación nunca se persiste en el navegador.
const setItem = vi.fn()
const almacen = { getItem: () => null, setItem, removeItem: vi.fn(), clear: vi.fn(), key: () => null, length: 0 } as Storage
globalThis.localStorage = almacen
globalThis.sessionStorage = almacen

describe('store de moderación', () => {
  beforeEach(() => {
    setItem.mockClear()
    setActivePinia(createPinia())
  })

  it('empieza sin sesión y sin cabeceras', () => {
    const m = useModeracionStore()
    expect(m.autenticado).toBe(false)
    expect(m.cabeceras()).toEqual({})
  })

  it('acepta una credencial de al menos 16 caracteres (sin espacios alrededor)', () => {
    const m = useModeracionStore()
    expect(m.iniciarSesion('  corta  ')).toBe(false)
    expect(m.iniciarSesion(`  ${'f'.repeat(40)}  `)).toBe(true)
    expect(m.cabeceras()).toEqual({ Authorization: `Bearer ${'f'.repeat(40)}` })
  })

  it('cerrar sesión borra la credencial', () => {
    const m = useModeracionStore()
    m.iniciarSesion('f'.repeat(40))
    m.cerrarSesion()
    expect(m.autenticado).toBe(false)
    expect(m.cabeceras()).toEqual({})
  })

  it('nunca guarda la credencial en localStorage ni sessionStorage', () => {
    useModeracionStore().iniciarSesion('f'.repeat(40))
    expect(setItem).not.toHaveBeenCalled()
  })
})
