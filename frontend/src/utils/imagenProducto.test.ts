import { describe, expect, it } from 'vitest'
import { ilustracionDe, urlSegura } from './imagenProducto'

describe('imagen del producto', () => {
  it('cada categoría del catálogo tiene su ilustración, sin importar tildes ni mayúsculas', () => {
    expect(ilustracionDe('Granos').icono).toBe('granos')
    expect(ilustracionDe('lacteos').icono).toBe('lacteos')
    expect(ilustracionDe('Frutas y verduras').icono).toBe('frutas')
    expect(ilustracionDe('ASEO').icono).toBe('aseo')
  })

  it('las categorías que trae el catálogo de SIPSA también tienen ilustración', () => {
    expect(ilustracionDe('Carnes').icono).toBe('carnes')
    expect(ilustracionDe('Pescados').icono).toBe('pescados')
    expect(ilustracionDe('Despensa').icono).toBe('despensa')
  })

  it('una categoría desconocida usa la ilustración genérica', () => {
    expect(ilustracionDe('Mascotas').icono).toBe('generico')
  })

  it('solo acepta URLs https (defensa en el cliente además de la API)', () => {
    expect(urlSegura('https://images.openfoodfacts.org/a.jpg')).toBe('https://images.openfoodfacts.org/a.jpg')
    expect(urlSegura('javascript:alert(1)')).toBeNull()
    expect(urlSegura('http://x.example/a.jpg')).toBeNull()
    expect(urlSegura(undefined)).toBeNull()
  })

  it('acepta las fotos empacadas con la app (rutas locales de /productos/) y nada más', () => {
    expect(urlSegura('/productos/lulo.webp')).toBe('/productos/lulo.webp')
    expect(urlSegura('/productos/papa-criolla-limpia.webp')).toBe('/productos/papa-criolla-limpia.webp')
    expect(urlSegura('//evil.example/productos/a.webp')).toBeNull()
    expect(urlSegura('/productos/../index.html')).toBeNull()
    expect(urlSegura('/otra/carpeta/a.webp')).toBeNull()
    expect(urlSegura('/productos/a.svg')).toBeNull()
  })
})
