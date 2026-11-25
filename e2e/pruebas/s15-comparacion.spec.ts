import { expect, test } from '@playwright/test'
import { idDeProducto } from '../soporte/flujos'

/*
 * S-15 (RF-13, RF-15, RN-10, EC-06) y S-11 (RF-11, EC-07): buscar un producto, comparar con el
 * criterio de orden visible y cambiar a «más cercanos» con la ubicación del dispositivo.
 */
test.use({
  geolocation: { latitude: 4.5352, longitude: -75.6757 }, // Centro de Armenia (cerca de Tienda Doña Rosa)
  permissions: ['geolocation'],
})

interface ItemApi {
  posicion: number
  precioPorUnidad: string
  establecimiento: { nombre: string; distanciaKm?: number }
}

test('S-15 buscar y comparar: el orden por precio/unidad es ascendente y el criterio está a la vista', async ({ page }) => {
  await page.goto('/buscar')
  await page.getByRole('searchbox').fill('arroz')
  const respuesta = page.waitForResponse((r) => new URL(r.url()).pathname.endsWith('/v1/comparaciones') && r.request().method() === 'GET')
  await page.getByRole('link', { name: /Arroz blanco/ }).first().click()

  const cuerpo = await (await respuesta).json()
  const items = cuerpo.items as ItemApi[]
  expect(items.length).toBeGreaterThan(1)
  const precios = items.map((i) => Number(i.precioPorUnidad))
  expect(precios).toEqual([...precios].sort((a, b) => a - b))

  // El criterio declarado (RN-10) se muestra tal cual lo redacta la API.
  await expect(page.getByRole('heading', { level: 1, name: 'Arroz blanco' })).toBeVisible()
  await expect(page.getByText(cuerpo.criterioOrden.descripcion)).toBeVisible()
  await expect(page.getByRole('button', { name: 'Precio/unidad' })).toHaveAttribute('aria-pressed', 'true')

  // La tarjeta en primera posición es la más barata por unidad.
  const tarjetas = page.getByRole('list', { name: 'Comparación de precios' }).getByRole('listitem')
  await expect(tarjetas).toHaveCount(items.length)
  await expect(tarjetas.first()).toContainText(items[0]!.establecimiento.nombre)

  // EC-07: la respuesta pública no expone a quién reportó.
  expect(JSON.stringify(cuerpo)).not.toMatch(/reportado_por|reportadoPor|correo|cuentaId/)
})

test('S-11 ordenar por distancia usa la ubicación del dispositivo y muestra la distancia de cada tienda', async ({ page, request }) => {
  await page.goto(`/comparar/${await idDeProducto(request, 'Panela')}`)
  await expect(page.getByRole('list', { name: 'Comparación de precios' })).toBeVisible()

  const respuesta = page.waitForResponse((r) => new URL(r.url()).pathname.endsWith('/v1/comparaciones') && r.url().includes('orden=distancia'))
  await page.getByRole('button', { name: 'Distancia' }).click()
  const cuerpo = await (await respuesta).json()
  const distancias = (cuerpo.items as ItemApi[]).map((i) => i.establecimiento.distanciaKm ?? Infinity)

  expect(distancias.every(Number.isFinite)).toBe(true)
  expect(distancias).toEqual([...distancias].sort((a, b) => a - b))
  await expect(page.getByRole('button', { name: 'Distancia' })).toHaveAttribute('aria-pressed', 'true')
  await expect(page.getByText(cuerpo.criterioOrden.descripcion)).toBeVisible()
})
