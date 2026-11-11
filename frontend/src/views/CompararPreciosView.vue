<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, MapPin, Clock, ArrowUpDown, AlertTriangle, ShieldCheck, Store } from 'lucide-vue-next'
import { productos, getComparacionMock } from '@/repositories/mock-data'
import type { PrecioComparacion } from '@/types'
import BadgeVigencia from '@/components/BadgeVigencia.vue'
import BadgeConfianza from '@/components/BadgeConfianza.vue'

const route = useRoute()
const productoId = route.params.productoId as string
const producto = productos.find(p => p.id === productoId)

const orden = ref<'precio' | 'distancia'>('precio')
const resultadosCrudos = ref<PrecioComparacion[]>([])

const resultados = computed(() => {
  const items = [...resultadosCrudos.value]
  if (orden.value === 'distancia') {
    items.sort((a, b) => (a.distanciaMetros ?? Infinity) - (b.distanciaMetros ?? Infinity))
  }
  return items
})

const criterioOrden = computed(() =>
  orden.value === 'precio' ? 'precio_unidad_asc' : 'distancia_asc'
)

onMounted(() => {
  resultadosCrudos.value = getComparacionMock(productoId)
})

function formatearDistancia(metros: number | null): string {
  if (metros === null) return '--'
  if (metros < 1000) return `${metros} m`
  return `${(metros / 1000).toFixed(1)} km`
}

function tiempoRelativo(fecha: string): string {
  const diff = Date.now() - new Date(fecha).getTime()
  const horas = Math.floor(diff / 3600000)
  if (horas < 1) return 'hace menos de 1h'
  if (horas < 24) return `hace ${horas}h`
  const dias = Math.floor(horas / 24)
  return `hace ${dias}d`
}
</script>

<template>
  <section aria-labelledby="titulo-comparar">
    <!-- Header con back -->
    <div class="mb-4 flex items-center gap-3">
      <router-link to="/buscar" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" aria-label="Volver a buscar">
        <ArrowLeft class="h-5 w-5" />
      </router-link>
      <div v-if="producto">
        <h1 id="titulo-comparar" class="text-lg font-bold text-slate-900">{{ producto.nombre }}</h1>
        <p class="text-xs text-slate-400">{{ producto.presentacion }} · {{ producto.marca }}</p>
      </div>
    </div>

    <!-- Selector de orden (criterio transparente — EC-06, RN-10) -->
    <div class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2">
      <div class="flex items-center gap-1.5 text-xs text-slate-500" role="group" aria-label="Criterio de orden">
        <ArrowUpDown class="h-3.5 w-3.5" aria-hidden="true" />
        <span>Ordenar por:</span>
      </div>
      <div class="flex gap-1">
        <button
          class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
          :class="orden === 'precio' ? 'bg-primary-600 text-white' : 'text-slate-500 hover:bg-slate-100'"
          :aria-pressed="orden === 'precio'"
          @click="orden = 'precio'"
        >
          Precio/unidad
        </button>
        <button
          class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
          :class="orden === 'distancia' ? 'bg-primary-600 text-white' : 'text-slate-500 hover:bg-slate-100'"
          :aria-pressed="orden === 'distancia'"
          @click="orden = 'distancia'"
        >
          Distancia
        </button>
      </div>
    </div>

    <!-- Indicador de criterio (transparencia — neutralidad) -->
    <p class="mb-3 text-right text-[10px] text-slate-300" aria-live="polite">
      criterio: {{ criterioOrden }}
    </p>

    <!-- Lista de resultados -->
    <ul v-if="resultados.length > 0" class="space-y-3" aria-label="Comparacion de precios">
      <li
        v-for="(item, idx) in resultados"
        :key="item.id"
        class="rounded-xl border bg-white p-4 transition-shadow hover:shadow-sm"
        :class="item.estadoVigencia === 'PUEDE_ESTAR_DESACTUALIZADO' || item.estadoConfianza === 'POR_VERIFICAR'
          ? 'border-l-3 border-l-warning-500 border-t-slate-200 border-r-slate-200 border-b-slate-200'
          : 'border-slate-200'"
      >
        <!-- Posicion + Precio unitario -->
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-2">
            <span
              class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
              :class="idx === 0 ? 'bg-primary-100 text-primary-700' : 'bg-slate-100 text-slate-400'"
            >
              {{ idx + 1 }}
            </span>
            <div>
              <p class="text-sm font-semibold text-slate-800">{{ item.establecimiento }}</p>
              <p class="flex items-center gap-1 text-xs text-slate-400">
                <Store class="h-3 w-3" aria-hidden="true" />
                {{ item.tipoEstablecimiento }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-lg font-extrabold text-primary-700">{{ item.precioUnidadDisplay }}</p>
            <p class="text-xs text-slate-400">{{ item.montoDisplay }}</p>
          </div>
        </div>

        <!-- Metadatos -->
        <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
          <span v-if="item.distanciaMetros !== null" class="flex items-center gap-1">
            <MapPin class="h-3 w-3" aria-hidden="true" />
            {{ formatearDistancia(item.distanciaMetros) }}
          </span>
          <span class="flex items-center gap-1">
            <Clock class="h-3 w-3" aria-hidden="true" />
            {{ tiempoRelativo(item.registradoEn) }}
          </span>
          <span v-if="item.fuenteTipo === 'oficial'" class="flex items-center gap-1 text-success-700">
            <ShieldCheck class="h-3 w-3" aria-hidden="true" />
            Precio oficial
          </span>
        </div>

        <!-- Badges de estado -->
        <div class="mt-2 flex gap-1.5">
          <BadgeVigencia :estado="item.estadoVigencia" />
          <BadgeConfianza :estado="item.estadoConfianza" />
        </div>

        <!-- Advertencias (EC-03, RN-09: advertencia explícita) -->
        <div
          v-if="item.estadoVigencia === 'PUEDE_ESTAR_DESACTUALIZADO'"
          class="mt-2 flex items-start gap-1.5 rounded-lg bg-warning-50 p-2 text-xs text-warning-700"
          role="alert"
        >
          <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span>Este precio puede estar desactualizado. Verifica en la tienda.</span>
        </div>
        <div
          v-if="item.estadoConfianza === 'POR_VERIFICAR'"
          class="mt-2 flex items-start gap-1.5 rounded-lg bg-warning-50 p-2 text-xs text-warning-700"
          role="alert"
        >
          <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span>Este precio aun no ha sido corroborado por otros usuarios.</span>
        </div>
      </li>
    </ul>

    <div v-else class="py-12 text-center">
      <p class="text-sm text-slate-400">No hay precios disponibles para este producto.</p>
    </div>
  </section>
</template>
