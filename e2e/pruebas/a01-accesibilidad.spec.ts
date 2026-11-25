import { expect, test, type Page } from '@playwright/test'
import { auditarAccesibilidad, describirViolaciones } from '../soporte/axe'
import { idDeProducto } from '../soporte/flujos'

/*
 * A-01 (EC-08, M-11): 0 violaciones WCAG 2.2 A/AA detectables por axe en las pantallas de la PWA,
 * con prioridad en los flujos críticos de consulta y registro. Cada pantalla es una prueba aparte
 * para que el informe diga exactamente dónde falla. El resumen exportable queda en
 * resultados/axe/resumen.md.
 */

async function auditar(page: Page, nombre: string) {
  await page.waitForLoadState('networkidle')
  const resultado = await auditarAccesibilidad(page, nombre)
  expect(resultado.violaciones, describirViolaciones(resultado)).toEqual([])
}

const PANTALLAS_SIMPLES: [nombre: string, ruta: string, listo: (p: Page) => Promise<void>][] = [
  ['inicio', '/', async (p) => { await expect(p.getByRole('heading', { level: 1 })).toBeVisible() }],
  ['buscar', '/buscar', async (p) => { await expect(p.getByRole('searchbox')).toBeVisible() }],
  ['canasta', '/canasta', async (p) => { await expect(p.getByRole('heading', { level: 1 })).toBeVisible() }],
  ['establecimientos-lista', '/establecimientos', async (p) => { await expect(p.getByRole('heading', { level: 1 })).toBeVisible() }],
  ['establecimientos-mapa', '/establecimientos?vista=mapa', async (p) => { await expect(p.locator('.leaflet-container')).toBeVisible() }],
  ['perfil', '/perfil', async (p) => { await expect(p.getByRole('heading', { level: 1 })).toBeVisible() }],
  ['cuenta', '/cuenta', async (p) => { await expect(p.getByRole('tablist')).toBeVisible() }],
  ['comercio', '/comercio', async (p) => { await expect(p.getByRole('heading', { level: 1 })).toBeVisible() }],
  ['privacidad', '/privacidad', async (p) => { await expect(p.getByRole('heading', { level: 1 })).toBeVisible() }],
]

for (const [nombre, ruta, listo] of PANTALLAS_SIMPLES) {
  test(`A-01 ${nombre}`, async ({ page }) => {
    await page.goto(ruta)
    await listo(page)
    await auditar(page, nombre)
  })
}

test('A-01 comparar (flujo de consulta)', async ({ page, request }) => {
  await page.goto(`/comparar/${await idDeProducto(request, 'Arroz blanco')}`)
  await expect(page.getByRole('list', { name: 'Comparación de precios' })).toBeVisible()
  await auditar(page, 'comparar')

  // El formulario de «Marcar como dudoso» abierto también se audita (sin sesión invita a entrar).
  await page.getByRole('button', { name: /Marcar como dudoso/ }).first().click()
  await auditar(page, 'comparar-acciones')
})

test('A-01 registrar, los cuatro pasos y la confirmación (flujo de registro)', async ({ page }) => {
  await page.goto('/registrar')
  const tiendas = page.getByRole('radiogroup', { name: 'Seleccionar establecimiento' })
  await expect(tiendas.getByRole('radio').first()).toBeVisible()
  await auditar(page, 'registrar-1-tienda')

  await tiendas.getByRole('radio', { name: /Fruver La Cosecha/ }).click()
  await page.getByRole('button', { name: 'Siguiente' }).click()
  await page.getByRole('radiogroup', { name: 'Seleccionar producto' }).getByRole('radio', { name: /Leche entera/ }).click()
  await page.getByRole('radiogroup', { name: 'Presentación' }).getByRole('radio', { name: /Bolsa 1 L/ }).click()
  await auditar(page, 'registrar-2-producto')

  await page.getByRole('button', { name: 'Siguiente' }).click()
  await page.getByLabel(/Precio en pesos/).fill('abc') // estado de error visible
  await auditar(page, 'registrar-3-precio-con-error')

  await page.getByLabel(/Precio en pesos/).fill('3700')
  await page.getByRole('button', { name: 'Siguiente' }).click()
  await auditar(page, 'registrar-4-confirmar')

  await page.getByRole('button', { name: 'Confirmar y registrar' }).click()
  await expect(page.getByRole('heading', { name: 'Precio registrado' })).toBeVisible()
  await auditar(page, 'registrar-5-registrado')
})
