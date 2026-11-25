import { expect, test, type Page, type Route } from '@playwright/test'
import { API } from '../soporte/entorno'
import { crearCuentaEnLaPwa, registrarEnTiendaElegida } from '../soporte/flujos'

/*
 * S-09 (RF-09, EC-09, M-12): el reportante registra 3 precios sin conexión y luego se reconecta.
 * Medida: 100 % sincronizados y 0 duplicados.
 *
 * Además se simula el peor caso de la idempotencia: el servidor guarda el primer reporte pero la
 * respuesta se pierde en el camino. La PWA debe reintentarlo con la MISMA Idempotency-Key y el
 * servidor no debe crear un segundo registro.
 *
 * La comprobación final se hace en el servidor (GET /v1/mis-aportes con la cuenta del reportante),
 * no en lo que muestra la PWA.
 */
const TIENDA = 'Tienda El Paisa'
const REPORTES = [
  { producto: 'Lenteja', precio: '4100' },
  { producto: 'Café molido', precio: '10200' },
  { producto: 'Jabón en barra', precio: '6500' },
]

interface Envio {
  clave: string
  estado: number | 'sin-red' | 'respuesta-perdida'
  observacionId?: string
}

/** Espera a que el service worker controle la página: desde ahí la PWA funciona sin conexión. */
async function prepararUsoSinConexion(page: Page) {
  await page.goto('/')
  await page.evaluate(async () => {
    await navigator.serviceWorker.ready
  })
  await page.reload()
  await expect.poll(() => page.evaluate(() => !!navigator.serviceWorker.controller)).toBe(true)
}

test('S-09 tres precios registrados sin conexión se sincronizan al volver la red, sin duplicados (M-12)', async ({ page, context }) => {
  await prepararUsoSinConexion(page)
  await crearCuentaEnLaPwa(page, 's09') // sin recargar desde aquí: la sesión vive en memoria

  // Registro de las peticiones de reporte con su clave, y simulación de la respuesta perdida.
  const envios: Envio[] = []
  let sinRed = false
  let perderLaPrimeraRespuesta = true
  await context.route('**/v1/reportes-precio', async (route: Route) => {
    const clave = route.request().headers()['idempotency-key'] ?? ''
    if (sinRed) {
      envios.push({ clave, estado: 'sin-red' })
      return route.abort('internetdisconnected')
    }
    const respuesta = await route.fetch()
    const cuerpo = (await respuesta.json()) as { observacionId?: string }
    if (perderLaPrimeraRespuesta) {
      perderLaPrimeraRespuesta = false
      envios.push({ clave, estado: 'respuesta-perdida', observacionId: cuerpo.observacionId })
      return route.abort('connectionreset') // el servidor ya lo guardó; el teléfono no se enteró
    }
    envios.push({ clave, estado: respuesta.status(), observacionId: cuerpo.observacionId })
    return route.fulfill({ response: respuesta })
  })

  // En línea: se elige la tienda y se abren los productos (quedan en la caché del service worker).
  await page.getByRole('navigation').getByRole('link', { name: 'Registrar' }).click()
  await page.getByRole('radiogroup', { name: 'Seleccionar establecimiento' }).getByRole('radio', { name: new RegExp(TIENDA) }).click()
  await page.getByRole('button', { name: 'Siguiente' }).click()
  const productos = page.getByRole('radiogroup', { name: 'Seleccionar producto' })
  for (const { producto } of REPORTES) {
    const detalle = page.waitForResponse((r) => /\/v1\/productos\/[0-9a-f-]{36}$/.test(r.url()))
    await productos.getByRole('radio', { name: new RegExp(producto) }).click()
    await detalle
  }

  // Sin conexión: los tres reportes quedan guardados en el teléfono.
  sinRed = true
  await context.setOffline(true)
  for (const [i, reporte] of REPORTES.entries()) {
    if (i > 0) await page.getByRole('button', { name: new RegExp(`Registrar otro precio en ${TIENDA}`) }).click()
    await registrarEnTiendaElegida(page, reporte)
    await expect(page.getByRole('heading', { name: 'Guardado en tu teléfono' })).toBeVisible()
  }
  await page.getByRole('navigation').getByRole('link', { name: 'Perfil' }).click()
  await expect(page.getByText(/3 (reportes )?pendientes?|3 en espera/i)).toBeVisible()

  // Vuelve la red: la PWA sincroniza sola (evento `online`), sin que el usuario haga nada.
  sinRed = false
  await context.setOffline(false)
  await expect(page.getByText('No tienes reportes pendientes.')).toBeVisible({ timeout: 20_000 })

  // Cada reporte llegó con su propia clave; el que perdió la respuesta se reenvió con la misma.
  const claves = new Set(envios.map((e) => e.clave))
  expect(claves.size).toBe(3)
  const perdido = envios.find((e) => e.estado === 'respuesta-perdida')!
  const reenvio = envios.filter((e) => e.clave === perdido.clave && typeof e.estado === 'number')
  expect(reenvio).toHaveLength(1)
  expect(reenvio[0]!.observacionId).toBe(perdido.observacionId) // el servidor devolvió el mismo registro

  // En el servidor: exactamente 3 aportes de esta cuenta, con los precios reportados (0 duplicados).
  const misAportes = page.waitForResponse((r) => r.url().startsWith(`${API}/mis-aportes`) && r.ok())
  await page.getByRole('link', { name: /Mis aportes/ }).click()
  const { items } = (await (await misAportes).json()) as { items: { precio: string; establecimiento: string | null }[] }
  expect(items).toHaveLength(3)
  expect(items.map((i) => Number(i.precio)).sort((a, b) => a - b)).toEqual(REPORTES.map((r) => Number(r.precio)).sort((a, b) => a - b))
  expect(items.every((i) => i.establecimiento === TIENDA)).toBe(true)
})
