import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import { CARPETA_AXE, ETIQUETAS_WCAG, type ResultadoPagina } from './axe'

/** Junta los resultados de axe de cada página en resultados/axe/resumen.md (evidencia de A-01, M-11). */
export default function resumirAxe(): void {
  if (!existsSync(CARPETA_AXE)) return

  const paginas: ResultadoPagina[] = readdirSync(CARPETA_AXE)
    .filter((f) => f.endsWith('.json'))
    .map((f) => JSON.parse(readFileSync(join(CARPETA_AXE, f), 'utf8')) as ResultadoPagina)
    .sort((a, b) => a.pagina.localeCompare(b.pagina))

  const total = paginas.reduce((n, p) => n + p.violaciones.length, 0)
  const filas = paginas.map((p) => {
    const nodos = p.violaciones.reduce((n, v) => n + v.nodos.length, 0)
    return `| ${p.pagina} | \`${p.ruta}\` | ${p.reglasEvaluadas} | ${p.violaciones.length} | ${nodos} |`
  })
  const detalle = paginas
    .filter((p) => p.violaciones.length > 0)
    .flatMap((p) => [
      `### ${p.pagina}`,
      ...p.violaciones.map((v) => `- **${v.id}** (${v.impacto}): ${v.descripcion} — ${v.nodos.length} elemento(s). [Regla](${v.ayuda})`),
    ])

  const md = [
    '# Reporte de accesibilidad automático (A-01, EC-08, M-11)',
    '',
    `- **Fecha:** ${new Date().toISOString()}`,
    `- **Herramienta:** axe-core vía @axe-core/playwright, reglas ${ETIQUETAS_WCAG.join(', ')}`,
    '- **Navegador:** Chromium, perfil móvil (Pixel 7)',
    `- **Resultado M-11:** ${total} violaciones A/AA (umbral aceptable: 0) → ${total === 0 ? '**cumple**' : '**no cumple**'}`,
    '',
    '| Página | Ruta | Reglas evaluadas | Violaciones | Elementos afectados |',
    '|---|---|---|---|---|',
    ...filas,
    '',
    ...(detalle.length ? ['## Detalle', '', ...detalle, ''] : []),
    'axe detecta de forma automática una parte de los criterios WCAG; la revisión manual con lector de pantalla y teclado (A-02) la complementa.',
    '',
  ].join('\n')

  writeFileSync(join(CARPETA_AXE, '..', 'resumen.md'), md)
}
