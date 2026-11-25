import { mkdirSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import AxeBuilder from '@axe-core/playwright'
import type { Page } from '@playwright/test'

/*
 * A-01 (EC-08, M-11): análisis automático con axe-core contra WCAG 2.2 A y AA.
 * Cada página auditada deja su resultado en resultados/axe/paginas/<nombre>.json; al final de la
 * ejecución soporte/resumen-axe.ts los junta en resultados/axe/resumen.md (evidencia exportable).
 */
export const ETIQUETAS_WCAG = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] as const
export const CARPETA_AXE = join(import.meta.dirname, '..', 'resultados', 'axe', 'paginas')

export interface ViolacionResumida {
  id: string
  impacto: string
  descripcion: string
  ayuda: string
  nodos: { selector: string; resumen: string }[]
}

export interface ResultadoPagina {
  pagina: string
  ruta: string
  fecha: string
  reglasEvaluadas: number
  violaciones: ViolacionResumida[]
}

export async function auditarAccesibilidad(page: Page, pagina: string): Promise<ResultadoPagina> {
  const analisis = await new AxeBuilder({ page }).withTags([...ETIQUETAS_WCAG]).analyze()

  const resultado: ResultadoPagina = {
    pagina,
    ruta: new URL(page.url()).pathname,
    fecha: new Date().toISOString(),
    reglasEvaluadas: analisis.passes.length + analisis.violations.length + analisis.incomplete.length,
    violaciones: analisis.violations.map((v) => ({
      id: v.id,
      impacto: v.impact ?? 'desconocido',
      descripcion: v.description,
      ayuda: v.helpUrl,
      nodos: v.nodes.map((n) => ({ selector: n.target.join(' '), resumen: n.failureSummary ?? '' })),
    })),
  }

  mkdirSync(CARPETA_AXE, { recursive: true })
  writeFileSync(join(CARPETA_AXE, `${pagina}.json`), JSON.stringify(resultado, null, 2))
  return resultado
}

/** Texto legible para el mensaje de la aserción cuando hay violaciones. */
export function describirViolaciones(r: ResultadoPagina): string {
  return r.violaciones
    .map((v) => `${v.id} (${v.impacto}): ${v.descripcion}\n${v.nodos.map((n) => `   - ${n.selector}`).join('\n')}`)
    .join('\n')
}
