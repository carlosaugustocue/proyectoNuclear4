/** Mínimo de caracteres que la API acepta en `q` (backend: regla min:2). */
export const MIN_CARACTERES_BUSQUEDA = 2

/**
 * Normaliza el texto de búsqueda: sin espacios sobrantes y `undefined` si es más corto que el mínimo,
 * para que la API devuelva la lista sin filtrar en vez de un error de validación.
 */
export function terminoBusqueda(texto: string | undefined): string | undefined {
  const limpio = texto?.trim() ?? ''
  return limpio.length >= MIN_CARACTERES_BUSQUEDA ? limpio : undefined
}

/** Quita `q` de los parámetros cuando no alcanza el mínimo (sin mutar el objeto recibido). */
export function conTerminoValido<T extends { q?: string }>(params: T): T {
  const { q, ...resto } = params
  const termino = terminoBusqueda(q)
  return (termino === undefined ? resto : { ...resto, q: termino }) as T
}
