import { describe, expect, it } from 'vitest'
import { rangoDePrecios } from './rangoComparacion'

/*
 * Franja de precios de la comparación: ubica cada tienda vigente entre el menor y el mayor precio por
 * unidad. Solo usa el criterio declarado (precio por unidad, RN-10): no reordena ni destaca por otra razón.
 */
const item = (establecimiento: string, precioPorUnidad: string, vigente = true) => ({
  observacionId: establecimiento,
  establecimiento: { nombre: establecimiento },
  precioPorUnidad,
  vigente,
})

describe('rango de precios de la comparación', () => {
  it('sin precios vigentes no hay rango', () => {
    expect(rangoDePrecios([], 'KG')).toBeNull()
    expect(rangoDePrecios([item('A', '5000', false)], 'KG')).toBeNull()
  })

  it('da el menor y el mayor precio por unidad y cuánto más cuesta la más cara', () => {
    const r = rangoDePrecios([item('A', '5450'), item('B', '6000'), item('C', '5750')], 'KG')!
    expect(r.minimo).toBe('$5.450/kg')
    expect(r.maximo).toBe('$6.000/kg')
    expect(r.diferenciaPorcentaje).toBe(10)
    expect(r.tiendas).toBe(3)
  })

  it('ubica cada tienda entre 0 (la más barata) y 1 (la más cara)', () => {
    const r = rangoDePrecios([item('A', '5000'), item('B', '6000'), item('C', '5500')], 'KG')!
    expect(r.puntos.map((p) => [p.nombre, p.posicion])).toEqual([['A', 0], ['B', 1], ['C', 0.5]])
  })

  it('ignora los precios históricos (no vigentes)', () => {
    const r = rangoDePrecios([item('A', '5000'), item('B', '9000', false)], 'KG')!
    expect(r.maximo).toBe('$5.000/kg')
    expect(r.tiendas).toBe(1)
  })

  it('con un solo precio, o todos iguales, la diferencia es 0 y los puntos van al centro', () => {
    const r = rangoDePrecios([item('A', '5000'), item('B', '5000')], 'L')!
    expect(r.diferenciaPorcentaje).toBe(0)
    expect(r.puntos.every((p) => p.posicion === 0.5)).toBe(true)
  })
})
