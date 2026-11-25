import { expect, type APIRequestContext, type Page } from '@playwright/test'
import { API, CONTRASENA_DE_PRUEBA, correoDePrueba } from './entorno'

/** Id de un producto sembrado, buscado por nombre en la API pública. */
export async function idDeProducto(request: APIRequestContext, nombre: string): Promise<string> {
  const respuesta = await request.get(`${API}/productos`, { params: { q: nombre, tamano: 5 } })
  expect(respuesta.ok()).toBe(true)
  const { items } = (await respuesta.json()) as { items: { id: string; nombre: string }[] }
  const producto = items.find((p) => p.nombre === nombre)
  if (!producto) throw new Error(`La semilla no tiene el producto «${nombre}»`)
  return producto.id
}

/**
 * Crea una cuenta desde la PWA (/cuenta?volver=…, el mismo camino del botón «Entrar» de las
 * tarjetas) y regresa a la página donde estaba. La sesión vive solo en memoria (ADR-0025):
 * después de esto no se debe recargar la página o se pierde.
 */
export async function crearCuentaEnLaPwa(page: Page, etiqueta: string): Promise<void> {
  const { pathname: volver } = new URL(page.url())
  await page.goto(`/cuenta?volver=${encodeURIComponent(volver)}`)
  await page.getByRole('tab', { name: 'Crear cuenta' }).click()
  await page.getByLabel('Correo').fill(correoDePrueba(etiqueta))
  await page.getByLabel('Contraseña', { exact: true }).fill(CONTRASENA_DE_PRUEBA)
  await page.getByRole('checkbox').check()
  await page.getByRole('button', { name: 'Crear cuenta', exact: true }).click()
  await page.waitForURL((url) => url.pathname === volver)
}

interface DatosReporte {
  establecimiento: string
  producto: string
  /** Marca (o categoría si no tiene): distingue «Lenteja · La Cosecha» de la genérica «Lenteja importada» de SIPSA. */
  marca: string
  presentacion?: string
  precio: string
}

const escapar = (texto: string) => texto.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

/**
 * Filtra la lista (solo muestra 30 de los más de 100 productos del catálogo) y elige el producto por nombre
 * y marca. Filtrar con el mismo texto repite la misma URL, que el service worker sirve sin conexión.
 */
export async function elegirProducto(page: Page, datos: { producto: string; marca: string }): Promise<void> {
  await page.getByLabel('Filtrar productos').fill(datos.producto)
  await page
    .getByRole('radiogroup', { name: 'Seleccionar producto' })
    .getByRole('radio', { name: new RegExp(`^${escapar(datos.producto)}\\s+${escapar(datos.marca)}`) })
    .click()
}

/** Recorre los pasos 2 a 4 del registro (la tienda ya está elegida) y confirma. */
export async function registrarEnTiendaElegida(page: Page, datos: Omit<DatosReporte, 'establecimiento'>): Promise<void> {
  await elegirProducto(page, datos)
  if (datos.presentacion) {
    await page.getByRole('radiogroup', { name: 'Presentación' }).getByRole('radio', { name: datos.presentacion }).click()
  }
  await page.getByRole('button', { name: 'Siguiente' }).click()
  await page.getByLabel(/Precio en pesos/).fill(datos.precio)
  await page.getByRole('button', { name: 'Siguiente' }).click()
  await page.getByRole('button', { name: 'Confirmar y registrar' }).click()
}

/** Registro completo desde /registrar: tienda, producto, presentación y precio. */
export async function registrarPrecio(page: Page, datos: DatosReporte): Promise<void> {
  await page.getByRole('radiogroup', { name: 'Seleccionar establecimiento' }).getByRole('radio', { name: new RegExp(datos.establecimiento) }).click()
  await page.getByRole('button', { name: 'Siguiente' }).click()
  await registrarEnTiendaElegida(page, datos)
}
