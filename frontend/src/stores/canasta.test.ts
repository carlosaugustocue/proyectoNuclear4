import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { MAX_PRODUCTOS, unidadesPermitidas, useCanastaStore } from './canasta'

const producto = (id: string, unidadBase = 'KG') => ({ productoId: id, nombre: `Producto ${id}`, unidadBase })

describe('store de la canasta (RF-27, ADR-0018)', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('agrega un producto con la unidad base como unidad por defecto', () => {
    const canasta = useCanastaStore()
    canasta.agregar(producto('arroz'))
    expect(canasta.items).toEqual([{ productoId: 'arroz', nombre: 'Producto arroz', unidadBase: 'KG', cantidad: '1', unidad: 'kg' }])
  })

  it('no repite un producto ya agregado', () => {
    const canasta = useCanastaStore()
    canasta.agregar(producto('arroz'))
    expect(canasta.agregar(producto('arroz'))).toBe(false)
    expect(canasta.items).toHaveLength(1)
  })

  it(`no permite más de ${MAX_PRODUCTOS} productos`, () => {
    const canasta = useCanastaStore()
    for (let i = 1; i <= MAX_PRODUCTOS; i++) canasta.agregar(producto(`p${i}`))
    expect(canasta.llena).toBe(true)
    expect(canasta.agregar(producto('p21'))).toBe(false)
    expect(canasta.items).toHaveLength(MAX_PRODUCTOS)
  })

  it('persiste la canasta en el dispositivo y la recupera', () => {
    useCanastaStore().agregar(producto('leche', 'L'))
    setActivePinia(createPinia())
    expect(useCanastaStore().items[0]).toMatchObject({ productoId: 'leche', unidad: 'L' })
  })

  it('arma la solicitud del contrato con cantidad, unidad y criterio', () => {
    const canasta = useCanastaStore()
    canasta.agregar(producto('arroz'))
    canasta.actualizar('arroz', { cantidad: '500', unidad: 'g' })
    canasta.criterio = 'total'
    expect(canasta.solicitud()).toEqual({ orden: 'total', items: [{ productoId: 'arroz', cantidad: '500', unidad: 'g' }] })
  })

  it('quita un producto y vacía la canasta', () => {
    const canasta = useCanastaStore()
    canasta.agregar(producto('a'))
    canasta.agregar(producto('b'))
    canasta.quitar('a')
    expect(canasta.items.map((i) => i.productoId)).toEqual(['b'])
    canasta.vaciar()
    expect(canasta.items).toEqual([])
  })

  it('ofrece solo unidades comparables con la unidad base (RN-03)', () => {
    expect(unidadesPermitidas('KG')).toEqual(['g', 'kg'])
    expect(unidadesPermitidas('L')).toEqual(['mL', 'L'])
    expect(unidadesPermitidas('UNIDAD')).toEqual(['unidad'])
  })
})
