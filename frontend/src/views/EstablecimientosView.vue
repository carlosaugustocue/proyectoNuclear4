<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { List, Map as IconoMapa, MapPin, Store, LocateFixed } from 'lucide-vue-next'
import MapaEstablecimientos from '@/components/MapaEstablecimientos.vue'
import { buscarEstablecimientos } from '@/api/establecimientos'
import { mensajeDeError } from '@/api/client'
import { useUbicacion } from '@/composables/useUbicacion'
import { TIPO_ESTABLECIMIENTO } from '@/utils/catalogo'
import { formatearDistancia } from '@/utils/formato'
import type { Establecimiento } from '@/types'

const RADIO_KM = 5
const establecimientos = ref<Establecimiento[]>([])
const cargando = ref(false)
const error = ref<string | null>(null)
const { coordenadas, estado, solicitar } = useUbicacion()

// La vista (lista o mapa) y el establecimiento enfocado viven en la URL: se pueden compartir y
// la comparación enlaza directo a «ver en el mapa».
const route = useRoute()
const router = useRouter()
const vista = computed(() => (route.query.vista === 'mapa' ? 'mapa' : 'lista'))
const enfocadoId = computed(() => (typeof route.query.id === 'string' ? route.query.id : null))
function cambiarVista(v: 'lista' | 'mapa') {
  router.replace({ query: { ...route.query, vista: v } })
}

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const cerca = coordenadas.value ? { ...coordenadas.value, radioKm: RADIO_KM } : {}
    establecimientos.value = (await buscarEstablecimientos({ ...cerca, tamano: 50 })).items
  } catch (e) {
    error.value = mensajeDeError(e)
  } finally {
    cargando.value = false
  }
}

async function cercaDeMi() {
  await solicitar()
  await cargar()
}

onMounted(cargar)
</script>

<template>
  <section aria-labelledby="titulo-establecimientos">
    <h1 id="titulo-establecimientos" class="mb-1 text-lg font-bold text-slate-900">Establecimientos</h1>
    <p class="mb-3 text-xs text-slate-500">Tiendas y supermercados del directorio en Armenia.</p>

    <button
      type="button"
      class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100"
      :aria-busy="estado === 'buscando'"
      @click="cercaDeMi"
    >
      <LocateFixed class="h-3.5 w-3.5" aria-hidden="true" />
      {{ estado === 'lista' ? `A menos de ${RADIO_KM} km de ti` : 'Ver los más cercanos' }}
    </button>
    <p v-if="estado === 'denegada'" class="mb-3 text-xs text-warning-700">No pudimos usar tu ubicación; mostramos todo el directorio.</p>

    <div class="mb-3 inline-flex rounded-lg border border-slate-200 bg-white p-0.5" role="group" aria-label="Cómo ver los establecimientos">
      <button
        v-for="op in ([['lista', 'Lista', List], ['mapa', 'Mapa', IconoMapa]] as const)"
        :key="op[0]"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-primary-500"
        :class="vista === op[0] ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
        :aria-pressed="vista === op[0]"
        @click="cambiarVista(op[0])"
      >
        <component :is="op[2]" class="h-3.5 w-3.5" aria-hidden="true" /> {{ op[1] }}
      </button>
    </div>

    <p v-if="error" class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>
    <p v-else-if="cargando" class="py-8 text-center text-sm text-slate-500" aria-live="polite">Cargando…</p>
    <MapaEstablecimientos
      v-else-if="vista === 'mapa'"
      :establecimientos="establecimientos"
      :enfocado-id="enfocadoId"
      :ubicacion-usuario="coordenadas"
    />
    <ul v-else class="space-y-2">
      <li
        v-for="est in establecimientos"
        :key="est.id"
        class="flex items-center gap-3 rounded-xl border bg-white p-4"
        :class="est.id === enfocadoId ? 'border-primary-400 ring-2 ring-primary-100' : 'border-slate-200'"
      >
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50">
          <Store class="h-5 w-5 text-primary-600" aria-hidden="true" />
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-slate-800">{{ est.nombre }}</p>
          <p class="text-xs text-slate-500">{{ TIPO_ESTABLECIMIENTO[est.tipo] ?? est.tipo }}<template v-if="est.barrio"> · {{ est.barrio }}</template></p>
          <p class="text-xs text-slate-500">{{ est.direccion }}</p>
        </div>
        <div v-if="est.distanciaKm !== undefined" class="flex items-center gap-1 text-xs text-slate-500">
          <MapPin class="h-3 w-3" aria-hidden="true" />
          {{ formatearDistancia(est.distanciaKm) }}
        </div>
      </li>
    </ul>
  </section>
</template>
