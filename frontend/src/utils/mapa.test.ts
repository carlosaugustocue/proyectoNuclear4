import { describe, expect, it } from 'vitest'
import { CENTRO_ARMENIA, enlaceOpenStreetMap, limitesDe } from './mapa'

describe('utilidades del mapa de establecimientos', () => {
  it('sin puntos no hay límites (el mapa usa el centro de Armenia)', () => {
    expect(limitesDe([])).toBeNull()
    expect(CENTRO_ARMENIA).toEqual({ lat: 4.5339, lon: -75.6811 })
  })

  it('calcula el rectángulo que contiene todos los puntos', () => {
    expect(limitesDe([{ lat: 4.54, lon: -75.66 }, { lat: 4.52, lon: -75.7 }, { lat: 4.53, lon: -75.68 }]))
      .toEqual([[4.52, -75.7], [4.54, -75.66]])
  })

  it('con un solo punto abre un margen para no hacer zoom infinito', () => {
    const [[s, o], [n, e]] = limitesDe([{ lat: 4.5, lon: -75.6 }])!
    expect(n - s).toBeGreaterThan(0)
    expect(e - o).toBeGreaterThan(0)
    expect((n + s) / 2).toBeCloseTo(4.5)
  })

  it('ignora coordenadas inválidas', () => {
    expect(limitesDe([{ lat: Number.NaN, lon: 1 }, { lat: 4.5, lon: -75.6 }, { lat: 200, lon: 0 }])![0][0]).toBeLessThan(4.5)
    expect(limitesDe([{ lat: Number.NaN, lon: 1 }])).toBeNull()
  })

  it('arma el enlace para abrir el punto en OpenStreetMap', () => {
    expect(enlaceOpenStreetMap({ lat: 4.5462, lon: -75.6651 }))
      .toBe('https://www.openstreetmap.org/?mlat=4.5462&mlon=-75.6651#map=18/4.5462/-75.6651')
  })
})
