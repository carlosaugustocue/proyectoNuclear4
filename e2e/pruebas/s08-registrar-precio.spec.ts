import { expect, test } from '@playwright/test'
import { registrarPrecio } from '../soporte/flujos'

/*
 * S-08 (RF-08, RN-08, EC-01): con conexión, el reportante registra un precio en cuatro pasos y la
 * API lo acepta una sola vez (202 con Idempotency-Key UUID). El reporte se envía en el acto: no
 * queda nada pendiente en el teléfono.
 */
test('S-08 registrar un precio con conexión: 202, una sola petición y nada pendiente', async ({ page }) => {
  const envios: { clave: string; estado: number }[] = []
  page.on('response', (r) => {
    if (r.url().endsWith('/v1/reportes-precio') && r.request().method() === 'POST') {
      envios.push({ clave: r.request().headers()['idempotency-key'] ?? '', estado: r.status() })
    }
  })

  await page.goto('/registrar')
  await registrarPrecio(page, { establecimiento: 'Minimercado La Esquina', producto: 'Panela', precio: '2.450' })

  await expect(page.getByRole('heading', { name: 'Precio registrado' })).toBeVisible()
  expect(envios).toHaveLength(1)
  expect(envios[0]!.estado).toBe(202)
  expect(envios[0]!.clave).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/)

  await page.getByRole('navigation').getByRole('link', { name: 'Perfil' }).click()
  await expect(page.getByText('No tienes reportes pendientes.')).toBeVisible()
})
