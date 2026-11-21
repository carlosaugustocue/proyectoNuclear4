import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useComercioStore } from './comercio'

// El entorno de Vitest es node: un localStorage espía para comprobar que la credencial nunca se persiste.
const setItem = vi.fn()
globalThis.localStorage = { getItem: () => null, setItem, removeItem: vi.fn(), clear: vi.fn(), key: () => null, length: 0 } as Storage

describe('store del comercio (ADR-0022)', () => {
  beforeEach(() => {
    setItem.mockClear()
    setActivePinia(createPinia())
  })

  it('empieza sin sesión', () => {
    const comercio = useComercioStore()
    expect(comercio.autenticado).toBe(false)
    expect(comercio.cabeceras()).toEqual({})
  })

  it('inicia sesión con una credencial de 40 caracteres y su establecimiento', () => {
    const comercio = useComercioStore()
    expect(comercio.iniciarSesion('a'.repeat(40), { id: 'tienda-a', nombre: 'Tienda A' })).toBe(true)
    expect(comercio.autenticado).toBe(true)
    expect(comercio.establecimiento?.nombre).toBe('Tienda A')
    expect(comercio.cabeceras()).toEqual({ Authorization: `Bearer ${'a'.repeat(40)}` })
  })

  it('rechaza una credencial con otra longitud', () => {
    const comercio = useComercioStore()
    expect(comercio.iniciarSesion('corta', { id: 'tienda-a', nombre: 'Tienda A' })).toBe(false)
    expect(comercio.autenticado).toBe(false)
  })

  it('nunca guarda la credencial en el almacenamiento del navegador', () => {
    useComercioStore().iniciarSesion('b'.repeat(40), { id: 'tienda-a', nombre: 'Tienda A' })
    expect(setItem).not.toHaveBeenCalled()
  })

  it('lleva la lista de promociones publicadas en la sesión y permite quitarlas', () => {
    const comercio = useComercioStore()
    comercio.registrarPromocion({ promocionId: 'p1', descripcion: '20 % de descuento', producto: 'Arroz' })
    comercio.registrarPromocion({ promocionId: 'p2', descripcion: 'Pague 2, lleve 3', producto: 'Leche' })
    comercio.quitarPromocion('p1')
    expect(comercio.promociones.map((p) => p.promocionId)).toEqual(['p2'])
  })

  it('cerrar sesión borra la credencial y las promociones de la memoria', () => {
    const comercio = useComercioStore()
    comercio.iniciarSesion('c'.repeat(40), { id: 'tienda-a', nombre: 'Tienda A' })
    comercio.registrarPromocion({ promocionId: 'p1', descripcion: 'x', producto: 'y' })
    comercio.cerrarSesion()
    expect(comercio.autenticado).toBe(false)
    expect(comercio.promociones).toEqual([])
    expect(comercio.cabeceras()).toEqual({})
  })
})
