<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { MapPin, Store, LocateFixed } from 'lucide-vue-next'
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

    <p v-if="error" class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>
    <p v-else-if="cargando" class="py-8 text-center text-sm text-slate-500" aria-live="polite">Cargando…</p>
    <ul v-else class="space-y-2">
      <li v-for="est in establecimientos" :key="est.id" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
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
