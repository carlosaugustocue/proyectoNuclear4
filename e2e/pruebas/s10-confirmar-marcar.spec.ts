import { expect, test } from '@playwright/test'
import { auditarAccesibilidad, describirViolaciones } from '../soporte/axe'
import { crearCuentaEnLaPwa, idDeProducto } from '../soporte/flujos'

/*
 * S-10 (RF-10, RF-12, RF-19; ADR-0026): sobre un precio vigente, «Sigue igual» corrobora el precio
 * publicado y «Marcar como dudoso» abre un caso de moderación; ambos exigen cuenta y aparecen en
 * «Mis aportes». Las acciones nunca cambian la posición en la comparación (RN-10).
 */
test('S-10 sin cuenta invita a entrar; con cuenta confirma, marca y lo ve en Mis aportes', async ({ page, request }) => {
  await page.goto(`/comparar/${await idDeProducto(request, 'Huevos AA')}`)
  const tarjetas = page.getByRole('list', { name: 'Comparación de precios' }).getByRole('listitem')
  await expect(tarjetas.first()).toBeVisible()
  const ordenInicial = await tarjetas.locator('p.font-semibold').first().allTextContents()

  // Sin cuenta: no se envía nada, se invita a entrar.
  await tarjetas.first().getByRole('button', { name: /Sigue igual/ }).click()
  await expect(page.getByText(/necesitas una cuenta/i)).toBeVisible()

  await crearCuentaEnLaPwa(page, 's10')
  await expect(tarjetas.first()).toBeVisible()

  // «Sigue igual» sobre el primer precio: 202 y mensaje de agradecimiento.
  const confirmacion = page.waitForResponse((r) => /\/confirmaciones$/.test(r.url()))
  await tarjetas.first().getByRole('button', { name: /Sigue igual/ }).click()
  expect((await confirmacion).status()).toBe(202)
  await expect(page.getByText(/tu confirmación ayuda/i)).toBeVisible()

  // «Marcar como dudoso» sobre el segundo, con motivo y comentario.
  const segunda = tarjetas.nth(1)
  await segunda.getByRole('button', { name: /Marcar como dudoso/ }).click()
  await segunda.getByText('Está desactualizado').click()
  await segunda.getByRole('textbox').fill('En la tienda ya está más caro')
  const marca = page.waitForResponse((r) => /\/marcas$/.test(r.url()))
  await segunda.getByRole('button', { name: 'Enviar' }).click()
  expect((await marca).status()).toBe(201)
  await expect(page.getByText(/Un moderador revisará/)).toBeVisible()

  // RN-10: confirmar o marcar no reordena la comparación.
  expect(await tarjetas.locator('p.font-semibold').first().allTextContents()).toEqual(ordenInicial)

  // Mis aportes muestra la confirmación con la reputación de la cuenta.
  await page.getByRole('navigation').getByRole('link', { name: 'Perfil' }).click()
  await page.getByRole('link', { name: /Mis aportes/ }).click()
  await expect(page.getByText('Tu reputación')).toBeVisible()
  await expect(page.getByRole('main').getByRole('listitem')).toHaveCount(1)

  const a11y = await auditarAccesibilidad(page, 'mis-aportes')
  expect(a11y.violaciones, describirViolaciones(a11y)).toEqual([])
})
