import { describe, expect, it } from 'vitest'
import { resumirReferencia } from './referenciaMayorista'
import type { ReferenciaMayorista } from '@/types'

/* Referencia mayorista SIPSA-DANE (RF-25, ADR-0010): se muestra aparte de la comparación de tiendas. */
const referencia = (serie: { mes: string; precio: string }[]): ReferenciaMayorista => ({
  productoId: 'p',
  unidadBase: 'KG',
  serie,
  ultimo: serie.at(-1) ?? null,
  mercados: ['Armenia, Mercar'],
  fuente: { nombre: 'SIPSA – DANE', descripcion: 'd', url: 'https://www.dane.gov.co/' },
  advertencia: 'Precio mayorista',
})

describe('resumen de la referencia mayorista', () => {
  it('sin serie no hay nada que mostrar', () => {
    expect(resumirReferencia(referencia([]))).toBeNull()
  })

  it('muestra el último precio por unidad base y el mes en español', () => {
    const r = resumirReferencia(referencia([{ mes: '2026-09', precio: '1400' }, { mes: '2026-10', precio: '1789' }]))!
    expect(r.precio).toBe('$1.789/kg')
    expect(r.mes).toBe('octubre de 2026')
  })

  it('calcula la variación frente al mes anterior, con signo y redondeada', () => {
    const sube = resumirReferencia(referencia([{ mes: '2026-09', precio: '1400' }, { mes: '2026-10', precio: '1789' }]))!
    const baja = resumirReferencia(referencia([{ mes: '2026-09', precio: '2000' }, { mes: '2026-10', precio: '1900' }]))!
    expect(sube.variacion).toEqual({ porcentaje: 28, sentido: 'sube' })
    expect(baja.variacion).toEqual({ porcentaje: -5, sentido: 'baja' })
  })

  it('con un solo mes no inventa una variación', () => {
    expect(resumirReferencia(referencia([{ mes: '2026-10', precio: '1789' }]))!.variacion).toBeNull()
  })

  it('normaliza los puntos de la mini gráfica entre 0 y 1 (el máximo arriba)', () => {
    const r = resumirReferencia(referencia([
      { mes: '2026-08', precio: '1000' }, { mes: '2026-09', precio: '2000' }, { mes: '2026-10', precio: '1500' },
    ]))!
    expect(r.puntos).toEqual([0, 1, 0.5])
  })

  it('una serie plana queda a media altura', () => {
    expect(resumirReferencia(referencia([{ mes: '2026-09', precio: '1000' }, { mes: '2026-10', precio: '1000' }]))!.puntos).toEqual([0.5, 0.5])
  })

  it('los huevos se publican por unidad', () => {
    const r = resumirReferencia({ ...referencia([{ mes: '2026-10', precio: '435' }]), unidadBase: 'UNIDAD' })!
    expect(r.precio).toBe('$435/und')
  })
})
