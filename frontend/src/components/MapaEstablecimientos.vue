<script setup lang="ts">
/*
 * Mapa de establecimientos (Leaflet + teselas de OpenStreetMap). Complementa la lista, no la reemplaza:
 * la lista sigue siendo la vía accesible con lector de pantalla (EC-08). Leaflet se carga de forma diferida
 * para no aumentar la carga inicial de la PWA. La ubicación del usuario solo se dibuja; nunca se envía ni
 * se guarda (privacidad por diseño, Ley 1581).
 */
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { Map as MapaLeaflet, LayerGroup } from 'leaflet'
import { TIPO_ESTABLECIMIENTO } from '@/utils/catalogo'
import { CENTRO_ARMENIA, enlaceOpenStreetMap, limitesDe, type Punto } from '@/utils/mapa'
import type { Establecimiento } from '@/types'

const props = defineProps<{
  establecimientos: Establecimiento[]
  /** Establecimiento a resaltar y abrir (p. ej., al llegar desde la comparación). */
  enfocadoId?: string | null
  ubicacionUsuario?: Punto | null
}>()

const contenedor = ref<HTMLDivElement | null>(null)
const error = ref<string | null>(null)
let mapa: MapaLeaflet | null = null
let capa: LayerGroup | null = null
let L: typeof import('leaflet') | null = null

// Colores por tipo: misma paleta semántica de la PWA (primario, éxito, advertencia).
const COLOR: Record<string, string> = { SUPERMERCADO: '#2563eb', TIENDA_BARRIO: '#16a34a', MINIMERCADO: '#d97706' }

function escapar(texto: string): string {
  return texto.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] ?? c)
}

function popup(e: Establecimiento): string {
  const tipo = TIPO_ESTABLECIMIENTO[e.tipo] ?? e.tipo
  const lineas = [
    `<strong>${escapar(e.nombre)}</strong>`,
    escapar(tipo) + (e.barrio ? ` · ${escapar(e.barrio)}` : ''),
    e.direccion ? escapar(e.direccion) : '',
    `<a href="${enlaceOpenStreetMap(e.ubicacion)}" target="_blank" rel="noopener noreferrer">Abrir en OpenStreetMap</a>`,
  ]
  return lineas.filter(Boolean).join('<br>')
}

function dibujar() {
  if (!L || !mapa || !capa) return
  capa.clearLayers()
  for (const e of props.establecimientos) {
    const enfocado = e.id === props.enfocadoId
    const marcador = L.circleMarker([e.ubicacion.lat, e.ubicacion.lon], {
      radius: enfocado ? 11 : 8,
      color: '#ffffff',
      weight: 2,
      fillColor: COLOR[e.tipo] ?? '#475569',
      fillOpacity: 0.95,
    })
      .bindPopup(popup(e))
      .bindTooltip(e.nombre)
      .addTo(capa)
    if (enfocado) {
      mapa.setView([e.ubicacion.lat, e.ubicacion.lon], 17)
      marcador.openPopup()
    }
  }
  if (props.ubicacionUsuario) {
    L.circleMarker([props.ubicacionUsuario.lat, props.ubicacionUsuario.lon], {
      radius: 7, color: '#ffffff', weight: 3, fillColor: '#0f172a', fillOpacity: 1,
    }).bindTooltip('Tu ubicación (no se guarda)').addTo(capa)
  }
  const enfocadoExiste = props.establecimientos.some((e) => e.id === props.enfocadoId)
  if (!enfocadoExiste) {
    const puntos = props.establecimientos.map((e) => e.ubicacion).concat(props.ubicacionUsuario ? [props.ubicacionUsuario] : [])
    const limites = limitesDe(puntos)
    if (limites) mapa.fitBounds(limites, { padding: [24, 24] })
    else mapa.setView([CENTRO_ARMENIA.lat, CENTRO_ARMENIA.lon], 13)
  }
}

onMounted(async () => {
  try {
    L = await import('leaflet')
    await import('leaflet/dist/leaflet.css')
    if (!contenedor.value) return
    mapa = L.map(contenedor.value, { scrollWheelZoom: false }).setView([CENTRO_ARMENIA.lat, CENTRO_ARMENIA.lon], 13)
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">colaboradores de OpenStreetMap</a>',
    }).addTo(mapa)
    capa = L.layerGroup().addTo(mapa)
    dibujar()
  } catch {
    error.value = 'No se pudo cargar el mapa. La lista sigue disponible.'
  }
})

watch(() => [props.establecimientos, props.enfocadoId, props.ubicacionUsuario], dibujar, { deep: true })

onBeforeUnmount(() => {
  mapa?.remove()
  mapa = null
})
</script>

<template>
  <div class="relative">
    <div
      ref="contenedor"
      class="h-[60vh] min-h-72 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
      role="region"
      aria-label="Mapa de establecimientos. La misma información está en la vista de lista."
    />
    <p v-if="error" class="mt-2 rounded-lg bg-warning-50 p-2 text-xs text-warning-800" role="status">{{ error }}</p>
    <ul class="mt-2 flex flex-wrap gap-3 text-[11px] text-slate-600" aria-label="Leyenda del mapa">
      <li class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[#2563eb]" aria-hidden="true" /> Supermercado</li>
      <li class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[#16a34a]" aria-hidden="true" /> Tienda de barrio</li>
      <li class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[#d97706]" aria-hidden="true" /> Minimercado</li>
    </ul>
  </div>
</template>
