<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, MapPin, Clock, ArrowUpDown, AlertTriangle, ShieldCheck, Store, Users } from 'lucide-vue-next'
import { compararPrecios } from '@/api/comparaciones'
import { mensajeDeError } from '@/api/client'
import { useUbicacion } from '@/composables/useUbicacion'
import BadgeVigencia from '@/components/BadgeVigencia.vue'
import BadgeConfianza from '@/components/BadgeConfianza.vue'
import BloquePromociones from '@/components/BloquePromociones.vue'
import { describirPresentacion, estadoVigencia, TIPO_ESTABLECIMIENTO } from '@/utils/catalogo'
import { formatearDistancia, formatearPesos, precioPorUnidadTexto, tiempoRelativo } from '@/utils/formato'
import type { OrdenComparacion, ResultadoComparacion } from '@/types'

const route = useRoute()
const productoId = computed(() => String(route.params.productoId))
const orden = ref<OrdenComparacion>('precio_unidad')
const incluirNoVigentes = ref(false)
const resultado = ref<ResultadoComparacion | null>(null)
const cargando = ref(false)
const error = ref<string | null>(null)
const { coordenadas, estado: estadoUbicacion, solicitar } = useUbicacion()

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    resultado.value = await compararPrecios({
      productoId: productoId.value,
      orden: orden.value,
      incluirNoVigentes: incluirNoVigentes.value || undefined,
      ...(coordenadas.value ?? {}),
    })
  } catch (e) {
    error.value = mensajeDeError(e, 'No pudimos cargar la comparación.')
  } finally {
    cargando.value = false
  }
}

async function ordenarPor(nuevo: OrdenComparacion) {
  if (nuevo === 'distancia' && !coordenadas.value) {
    await solicitar()
    if (!coordenadas.value) return // sin ubicación no se puede ordenar por distancia
  }
  orden.value = nuevo
}

watch([productoId, orden, incluirNoVigentes], cargar, { immediate: true })
</script>

<template>
  <section aria-labelledby="titulo-comparar">
    <div class="mb-4 flex items-center gap-3">
      <router-link to="/buscar" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Volver a buscar">
        <ArrowLeft class="h-5 w-5" />
      </router-link>
      <div>
        <h1 id="titulo-comparar" class="text-lg font-bold text-slate-900">{{ resultado?.producto.nombre ?? 'Comparación de precios' }}</h1>
        <p v-if="resultado" class="text-xs text-slate-500">{{ resultado.producto.marca ?? resultado.producto.categoria }}</p>
      </div>
    </div>

    <!-- Criterio de orden explícito (EC-06, RN-10, ADR-0009) -->
    <div class="mb-2 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2">
      <div class="flex items-center gap-1.5 text-xs text-slate-500">
        <ArrowUpDown class="h-3.5 w-3.5" aria-hidden="true" />
        <span id="etiqueta-orden">Ordenar por:</span>
      </div>
      <div class="flex gap-1" role="group" aria-labelledby="etiqueta-orden">
        <button
          type="button"
          class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
          :class="orden === 'precio_unidad' ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
          :aria-pressed="orden === 'precio_unidad'"
          @click="ordenarPor('precio_unidad')"
        >Precio/unidad</button>
        <button
          type="button"
          class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
          :class="orden === 'distancia' ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
          :aria-pressed="orden === 'distancia'"
          :aria-busy="estadoUbicacion === 'buscando'"
          @click="ordenarPor('distancia')"
        >Distancia</button>
      </div>
    </div>
    <p v-if="estadoUbicacion === 'denegada'" class="mb-2 text-xs text-warning-700">Para ordenar por distancia necesitamos tu ubicación (no se guarda).</p>
    <p v-if="resultado" class="mb-3 text-xs leading-relaxed text-slate-500" aria-live="polite">{{ resultado.criterioOrden.descripcion }}</p>

    <label class="mb-4 flex items-center gap-2 text-xs text-slate-600">
      <input v-model="incluirNoVigentes" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
      Mostrar también precios históricos
    </label>

    <p v-if="error" class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>
    <p v-else-if="cargando && !resultado" class="py-12 text-center text-sm text-slate-500" aria-live="polite">Consultando precios…</p>

    <ul v-else-if="resultado && resultado.items.length > 0" class="space-y-3" aria-label="Comparación de precios" :aria-busy="cargando">
      <li
        v-for="item in resultado.items"
        :key="`${item.establecimiento.id}-${item.presentacion.id}`"
        class="rounded-xl border bg-white p-4 transition-shadow hover:shadow-sm"
        :class="item.advertencia ? 'border-l-4 border-l-warning-500 border-y-slate-200 border-r-slate-200' : 'border-slate-200'"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-2">
            <span
              class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
              :class="item.posicion === 1 ? 'bg-primary-100 text-primary-700' : 'bg-slate-100 text-slate-600'"
            >{{ item.posicion }}</span>
            <div>
              <p class="text-sm font-semibold text-slate-800">{{ item.establecimiento.nombre }}</p>
              <p class="flex items-center gap-1 text-xs text-slate-500">
                <Store class="h-3 w-3" aria-hidden="true" />
                {{ TIPO_ESTABLECIMIENTO[item.establecimiento.tipo] ?? item.establecimiento.tipo }}<template v-if="item.establecimiento.barrio"> · {{ item.establecimiento.barrio }}</template>
              </p>
              <router-link
                :to="{ name: 'establecimientos', query: { vista: 'mapa', id: item.establecimiento.id } }"
                class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 hover:underline focus-visible:outline-2 focus-visible:outline-primary-500"
              >
                <MapPin class="h-3 w-3" aria-hidden="true" /> Ver en el mapa<span class="sr-only">: {{ item.establecimiento.nombre }}</span>
              </router-link>
            </div>
          </div>
          <div class="text-right">
            <p class="text-lg font-extrabold text-primary-700">{{ precioPorUnidadTexto(item.precioPorUnidad, resultado.unidadBase) }}</p>
            <p class="text-xs text-slate-500">{{ formatearPesos(item.precio) }} · {{ describirPresentacion(item.presentacion) }}</p>
          </div>
        </div>

        <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
          <span v-if="item.establecimiento.distanciaKm !== undefined" class="flex items-center gap-1">
            <MapPin class="h-3 w-3" aria-hidden="true" /> {{ formatearDistancia(item.establecimiento.distanciaKm) }}
          </span>
          <span class="flex items-center gap-1">
            <Clock class="h-3 w-3" aria-hidden="true" /> {{ tiempoRelativo(item.observadoEn) }}
          </span>
          <span v-if="item.confirmaciones > 0" class="flex items-center gap-1">
            <Users class="h-3 w-3" aria-hidden="true" /> {{ item.confirmaciones }} {{ item.confirmaciones === 1 ? 'confirmación' : 'confirmaciones' }}
          </span>
          <span v-if="item.fuente === 'COMERCIO'" class="flex items-center gap-1 text-success-700">
            <ShieldCheck class="h-3 w-3" aria-hidden="true" /> Precio oficial
          </span>
          <span v-else-if="item.fuente === 'EQUIPO_SEMILLA'">Dato de demostración</span>
        </div>

        <div class="mt-2 flex flex-wrap gap-1.5">
          <BadgeVigencia :estado="estadoVigencia(item)" />
          <BadgeConfianza :estado="item.estadoConfianza" />
        </div>

        <!-- Advertencia redactada por el servidor (RN-04, RN-09, EC-03) -->
        <p v-if="item.advertencia" class="mt-2 flex items-start gap-1.5 rounded-lg bg-warning-50 p-2 text-xs text-warning-700">
          <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span>{{ item.advertencia }}</span>
        </p>
      </li>
    </ul>

    <div v-else-if="resultado" class="py-12 text-center">
      <p class="text-sm text-slate-500">Aún no hay precios vigentes para este producto.</p>
      <router-link to="/registrar" class="mt-3 inline-block text-sm font-semibold text-primary-700 underline">Reporta el primero</router-link>
    </div>

    <BloquePromociones v-if="resultado" :promociones="resultado.contenidoPromocional" />
  </section>
</template>
