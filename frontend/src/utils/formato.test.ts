import { describe, expect, it } from 'vitest'
import { formatearDistancia, formatearPesos, precioPorUnidadTexto, tiempoRelativo } from './formato'

/* RN-02: el redondeo al peso ocurre SOLO al mostrar; la API entrega decimales exactos. */
describe('formato', () => {
  it('formatea pesos colombianos redondeando al peso', () => {
    expect(formatearPesos('5980.00')).toBe('$5.980')
    expect(formatearPesos('6585.90')).toBe('$6.586')
    expect(formatearPesos('500')).toBe('$500')
  })

  it('muestra el precio por unidad base con su unidad', () => {
    expect(precioPorUnidadTexto('5980.00', 'KG')).toBe('$5.980/kg')
    expect(precioPorUnidadTexto('5000.00', 'L')).toBe('$5.000/L')
    expect(precioPorUnidadTexto('550.00', 'UNIDAD')).toBe('$550/und')
  })

  it('formatea distancias en metros o kilómetros', () => {
    expect(formatearDistancia(0.08)).toBe('80 m')
    expect(formatearDistancia(3.72)).toBe('3.7 km')
    expect(formatearDistancia(undefined)).toBe('')
  })

  it('expresa la antigüedad del reporte en lenguaje natural', () => {
    const ahora = new Date('2026-11-16T12:00:00Z')
    expect(tiempoRelativo('2026-11-16T11:40:00Z', ahora)).toBe('hace menos de 1 h')
    expect(tiempoRelativo('2026-11-16T07:00:00Z', ahora)).toBe('hace 5 h')
    expect(tiempoRelativo('2026-11-14T12:00:00Z', ahora)).toBe('hace 2 días')
    expect(tiempoRelativo('2026-11-15T10:00:00Z', ahora)).toBe('hace 1 día')
  })
})
