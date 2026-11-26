import { describe, expect, it } from 'vitest'
import type { AxiosAdapter, InternalAxiosRequestConfig } from 'axios'
import apiClient from './client'
import { buscarProductos } from './catalogo'
import { buscarEstablecimientos } from './establecimientos'
import { terminoBusqueda } from './busqueda'

/* La API exige al menos 2 caracteres en `q`: con menos, la PWA busca sin filtro en vez de mostrar un error. */
function capturar(): InternalAxiosRequestConfig[] {
  const vistas: InternalAxiosRequestConfig[] = []
  const adapter: AxiosAdapter = async (config) => {
    vistas.push(config)
    return { data: { items: [], pagina: { pagina: 1, tamano: 20, total: 0 } }, status: 200, statusText: '', headers: {}, config }
  }
  apiClient.defaults.adapter = adapter
  return vistas
}

describe('término de búsqueda', () => {
  it('solo envía términos de 2 o más caracteres, sin espacios sobrantes', () => {
    expect(terminoBusqueda('a')).toBeUndefined()
    expect(terminoBusqueda('  a  ')).toBeUndefined()
    expect(terminoBusqueda('')).toBeUndefined()
    expect(terminoBusqueda(undefined)).toBeUndefined()
    expect(terminoBusqueda(' ar ')).toBe('ar')
  })

  it('buscar productos o tiendas con una sola letra no envía q', async () => {
    const vistas = capturar()
    await buscarProductos({ q: 'a', tamano: 30 })
    await buscarEstablecimientos({ q: 'b' })
    await buscarProductos({ q: 'arroz' })

    expect(vistas[0].params).toEqual({ tamano: 30 })
    expect(vistas[1].params).toEqual({})
    expect(vistas[2].params).toEqual({ q: 'arroz' })
  })
})
