<script setup lang="ts">
/*
 * Referencia mayorista SIPSA-DANE (RF-25, ADR-0010). Va DESPUÉS de la comparación y separada de ella:
 * es el precio promedio en las centrales de abasto, no el de una tienda, y no cambia el orden ni el total.
 */
import { computed, ref, watch } from 'vue'
import { Landmark, TrendingDown, TrendingUp, Minus, ExternalLink } from 'lucide-vue-next'
import { obtenerReferenciaMayorista } from '@/api/catalogo'
import { mensajeDeError } from '@/api/client'
import { resumirReferencia } from '@/utils/referenciaMayorista'
import type { ReferenciaMayorista } from '@/types'

const props = defineProps<{ productoId: string }>()

const referencia = ref<ReferenciaMayorista | null>(null)
const error = ref<string | null>(null)
const resumen = computed(() => (referencia.value ? resumirReferencia(referencia.value) : null))

const ANCHO = 120
const ALTO = 32
const trazo = computed(() => {
  const puntos = resumen.value?.puntos ?? []
  if (puntos.length < 2) return ''
  const paso = ANCHO / (puntos.length - 1)
  return puntos.map((y, i) => `${i === 0 ? 'M' : 'L'}${(i * paso).toFixed(1)},${(ALTO - 4 - y * (ALTO - 8)).toFixed(1)}`).join(' ')
})

watch(
  () => props.productoId,
  async (id) => {
    referencia.value = null
    error.value = null
    try {
      referencia.value = await obtenerReferenciaMayorista(id)
    } catch (e) {
      error.value = mensajeDeError(e, 'No pudimos cargar la referencia mayorista.')
    }
  },
  { immediate: true },
)
</script>

<template>
  <section v-if="resumen && referencia" aria-labelledby="titulo-referencia" class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-4">
    <div class="flex items-start gap-3">
      <span class="rounded-lg bg-slate-100 p-2 text-slate-700" aria-hidden="true"><Landmark class="h-5 w-5" /></span>
      <div class="min-w-0 flex-1">
        <h2 id="titulo-referencia" class="text-sm font-semibold text-slate-900">Referencia mayorista · {{ referencia.fuente.nombre }}</h2>
        <p class="text-xs text-slate-600">No entra en la comparación de tiendas</p>
      </div>
    </div>

    <div class="mt-3 flex items-end justify-between gap-4">
      <div>
        <p class="text-2xl font-bold tabular-nums text-slate-900">{{ resumen.precio }}</p>
        <p class="text-xs text-slate-600">Promedio de {{ resumen.mes }}</p>
        <p v-if="resumen.variacion" class="mt-1 inline-flex items-center gap-1 text-xs font-medium" :class="resumen.variacion.sentido === 'sube' ? 'text-red-700' : resumen.variacion.sentido === 'baja' ? 'text-emerald-700' : 'text-slate-700'">
          <TrendingUp v-if="resumen.variacion.sentido === 'sube'" class="h-3.5 w-3.5" aria-hidden="true" />
          <TrendingDown v-else-if="resumen.variacion.sentido === 'baja'" class="h-3.5 w-3.5" aria-hidden="true" />
          <Minus v-else class="h-3.5 w-3.5" aria-hidden="true" />
          {{ resumen.variacion.porcentaje > 0 ? '+' : '' }}{{ resumen.variacion.porcentaje }} % frente al mes anterior
        </p>
      </div>
      <svg v-if="trazo" :viewBox="`0 0 ${ANCHO} ${ALTO}`" :width="ANCHO" :height="ALTO" class="shrink-0 text-slate-500" role="img" :aria-label="`Tendencia de ${referencia.serie.length} meses`">
        <path :d="trazo" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </div>

    <p class="mt-3 text-xs text-slate-600">{{ referencia.advertencia }} Mercados: {{ referencia.mercados.join(' y ') }}.</p>
    <a :href="referencia.fuente.url" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary-700 underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-primary-600">
      Fuente: DANE, SIPSA <ExternalLink class="h-3 w-3" aria-hidden="true" /><span class="sr-only">(abre en otra pestaña)</span>
    </a>
  </section>
  <p v-else-if="error" class="mt-6 text-xs text-slate-600">{{ error }}</p>
</template>
