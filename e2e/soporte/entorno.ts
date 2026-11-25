/** URL del entorno de docker-compose.e2e.yml; se pueden apuntar a staging con variables de entorno. */
export const WEB = process.env.E2E_WEB_URL ?? 'http://localhost:4173'
export const API = process.env.E2E_API_URL ?? 'http://localhost:8090/v1'

/** Correo sintético y único por ejecución (nunca datos reales). */
export function correoDePrueba(etiqueta: string): string {
  return `e2e-${etiqueta}-${Date.now()}-${Math.floor(Math.random() * 1e6)}@ejemplo.test`
}

export const CONTRASENA_DE_PRUEBA = 'una frase larga de prueba e2e'
