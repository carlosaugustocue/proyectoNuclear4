import { defineConfig, devices } from '@playwright/test'
import { WEB } from './soporte/entorno'

/*
 * Pruebas de sistema (S-xx) y de accesibilidad (A-01) contra el entorno de docker-compose.e2e.yml:
 * la PWA compilada (con su CSP y su service worker) y la API de producción con la semilla.
 *
 * Un solo worker y sin reintentos: las pruebas comparten la base sembrada y un reintento
 * escondería justo la inestabilidad que estas pruebas deben revelar.
 */
export default defineConfig({
  testDir: './pruebas',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  forbidOnly: !!process.env.CI,
  timeout: 60_000,
  expect: { timeout: 10_000 },
  globalTeardown: './soporte/resumen-axe.ts',
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'resultados/informe' }]],
  outputDir: 'resultados/artefactos',
  use: {
    baseURL: WEB,
    locale: 'es-CO',
    timezoneId: 'America/Bogota',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      // El uso principal es el teléfono frente a la góndola (EC-01).
      name: 'movil-chromium',
      use: { ...devices['Pixel 7'] },
    },
  ],
})
