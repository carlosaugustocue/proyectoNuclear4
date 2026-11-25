<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, MapPin, Clock, AlertTriangle, ShieldCheck, Store, Users, Navigation, Tag, SearchX } from 'lucide-vue-next'
import { compararPrecios } from '@/api/comparaciones'
import { mensajeDeError } from '@/api/client'
import { useUbicacion } from '@/composables/useUbicacion'
import BadgeVigencia from '@/components/BadgeVigencia.vue'
import BadgeConfianza from '@/components/BadgeConfianza.vue'
import ImagenProducto from '@/components/ImagenProducto.vue'
import BloquePromociones from '@/components/BloquePromociones.vue'
import ReferenciaMayorista from '@/components/ReferenciaMayorista.vue'
import AccionesPrecio from '@/components/AccionesPrecio.vue'
import FranjaPrecios from '@/components/FranjaPrecios.vue'
import { rangoDePrecios } from '@/utils/rangoComparacion'
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
const rango = computed(() => (resultado.value ? rangoDePrecios(resultado.value.items, resultado.value.unidadBase) : null))
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
    <!-- Encabezado del producto -->
    <div class="mb-4 flex items-center gap-3">
      <router-link to="/buscar" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-slate-700 shadow-tarjeta ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-95" aria-label="Volver a buscar">
        <ArrowLeft class="h-5 w-5" aria-hidden="true" />
      </router-link>
      <ImagenProducto v-if="resultado" tamano="lg" :categoria="resultado.producto.categoria" :imagen="resultado.producto.imagen" />
      <div class="min-w-0">
        <h1 id="titulo-comparar" class="text-2xl font-bold leading-tight text-slate-900">{{ resultado?.producto.nombre ?? 'Comparación de precios' }}</h1>
        <p v-if="resultado" class="text-sm text-slate-500">{{ resultado.producto.marca ?? resultado.producto.categoria }}</p>
        <!-- Crédito de la foto (licencias CC BY / CC BY-SA): visible donde la foto se ve en detalle -->
        <p v-if="resultado?.producto.imagen?.credito" class="mt-0.5 text-[11px] leading-tight text-slate-500">{{ resultado.producto.imagen.credito }}</p>
      </div>
    </div>

    <!-- Resumen: el mejor precio y la franja de todas las tiendas (solo el criterio declarado, RN-10) -->
    <div v-if="rango" class="grano mb-4 overflow-hidden rounded-3xl bg-primary-950 p-5 text-white shadow-elevada">
      <div class="flex items-end justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-primary-200">Mejor precio por unidad</p>
          <p class="cifra mt-1 text-4xl font-bold leading-none">{{ rango.minimo }}</p>
        </div>
        <p v-if="rango.diferenciaPorcentaje > 0" class="rounded-2xl bg-accent-400 px-3 py-2 text-right text-slate-900">
          <span class="cifra block text-xl font-bold leading-none">{{ rango.diferenciaPorcentaje }} %</span>
          <span class="text-[11px] font-semibold leading-tight">más caro en la<br />tienda más cara</span>
        </p>
      </div>
      <FranjaPrecios class="mt-5" :rango="rango" />
    </div>

    <!-- Criterio de orden explícito (EC-06, RN-10, ADR-0009) -->
    <div class="mb-2 flex items-center gap-3">
      <span id="etiqueta-orden" class="text-sm font-semibold text-slate-700">Ordenar por</span>
      <div class="grid flex-1 grid-cols-2 rounded-xl bg-slate-200/60 p-1" role="group" aria-labelledby="etiqueta-orden">
        <button
          type="button"
          class="flex h-9 items-center justify-center gap-1.5 rounded-lg text-sm font-semibold transition duration-200"
          :class="orden === 'precio_unidad' ? 'bg-white text-slate-900 shadow-tarjeta' : 'text-slate-600 hover:text-slate-900'"
          :aria-pressed="orden === 'precio_unidad'"
          @click="ordenarPor('precio_unidad')"
        ><Tag class="h-4 w-4" aria-hidden="true" />Precio/unidad</button>
        <button
          type="button"
          class="flex h-9 items-center justify-center gap-1.5 rounded-lg text-sm font-semibold transition duration-200"
          :class="orden === 'distancia' ? 'bg-white text-slate-900 shadow-tarjeta' : 'text-slate-600 hover:text-slate-900'"
          :aria-pressed="orden === 'distancia'"
          :aria-busy="estadoUbicacion === 'buscando'"
          @click="ordenarPor('distancia')"
        ><Navigation class="h-4 w-4" aria-hidden="true" />Distancia</button>
      </div>
    </div>
    <p v-if="estadoUbicacion === 'denegada'" class="mb-2 text-xs text-warning-700">Para ordenar por distancia necesitamos tu ubicación (no se guarda).</p>
    <p v-if="resultado" class="mb-3 text-xs leading-relaxed text-slate-500" aria-live="polite">{{ resultado.criterioOrden.descripcion }}</p>

    <label class="mb-4 inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600">
      <input v-model="incluirNoVigentes" type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" />
      Mostrar también precios históricos
    </label>

    <p v-if="error" class="rounded-xl bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>
    <div v-else-if="cargando && !resultado" class="space-y-3" aria-live="polite" aria-busy="true">
      <span class="sr-only">Consultando precios…</span>
      <div v-for="n in 3" :key="n" class="h-40 animate-pulse rounded-2xl bg-white/70 ring-1 ring-slate-200" />
    </div>

    <ul v-else-if="resultado && resultado.items.length > 0" class="space-y-3" aria-label="Comparación de precios" :aria-busy="cargando">
      <li
        v-for="(item, i) in resultado.items"
        :key="`${item.establecimiento.id}-${item.presentacion.id}`"
        class="aparecer relative overflow-hidden rounded-2xl bg-white p-4 shadow-tarjeta ring-1 transition-shadow duration-200 hover:shadow-elevada"
        :class="item.advertencia ? 'ring-warning-500/40' : item.posicion === 1 && item.vigente ? 'ring-2 ring-primary-600' : 'ring-slate-200/80'"
        :style="{ animationDelay: `${Math.min(i, 6) * 35}ms` }"
      >
        <span v-if="item.advertencia" class="absolute inset-y-0 left-0 w-1 bg-warning-500" aria-hidden="true" />
        <div class="flex items-start justify-between gap-3">
          <div class="flex min-w-0 items-start gap-3">
            <span
              class="cifra grid h-8 w-8 shrink-0 place-items-center rounded-xl text-sm font-bold"
              :class="item.posicion === 1 ? 'bg-primary-700 text-white' : 'bg-slate-100 text-slate-700'"
            ><span class="sr-only">Posición </span>{{ item.posicion }}</span>
            <div class="min-w-0">
              <p class="text-base font-semibold leading-tight text-slate-900">{{ item.establecimiento.nombre }}</p>
              <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-500">
                <Store class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                {{ TIPO_ESTABLECIMIENTO[item.establecimiento.tipo] ?? item.establecimiento.tipo }}<template v-if="item.establecimiento.barrio"> · {{ item.establecimiento.barrio }}</template>
              </p>
              <router-link
                :to="{ name: 'establecimientos', query: { vista: 'mapa', id: item.establecimiento.id } }"
                class="mt-1 inline-flex items-center gap-1 rounded text-xs font-semibold text-primary-700 hover:underline"
              >
                <MapPin class="h-3.5 w-3.5" aria-hidden="true" /> Ver en el mapa<span class="sr-only">: {{ item.establecimiento.nombre }}</span>
              </router-link>
            </div>
          </div>
          <div class="shrink-0 text-right">
            <p class="cifra text-2xl font-bold leading-none" :class="item.posicion === 1 ? 'text-primary-700' : 'text-slate-900'">{{ precioPorUnidadTexto(item.precioPorUnidad, resultado.unidadBase) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ formatearPesos(item.precio) }} · {{ describirPresentacion(item.presentacion) }}</p>
          </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-1.5">
          <BadgeVigencia :estado="estadoVigencia(item)" />
          <BadgeConfianza :estado="item.estadoConfianza" />
          <span v-if="item.fuente === 'COMERCIO'" class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-800 ring-1 ring-inset ring-primary-600/20">
            <ShieldCheck class="h-3 w-3" aria-hidden="true" /> Precio oficial
          </span>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
          <span v-if="item.establecimiento.distanciaKm !== undefined" class="flex items-center gap-1">
            <MapPin class="h-3.5 w-3.5" aria-hidden="true" /> {{ formatearDistancia(item.establecimiento.distanciaKm) }}
          </span>
          <span class="flex items-center gap-1">
            <Clock class="h-3.5 w-3.5" aria-hidden="true" /> {{ tiempoRelativo(item.observadoEn) }}
          </span>
          <span v-if="item.confirmaciones > 0" class="flex items-center gap-1">
            <Users class="h-3.5 w-3.5" aria-hidden="true" /> {{ item.confirmaciones }} {{ item.confirmaciones === 1 ? 'confirmación' : 'confirmaciones' }}
          </span>
          <span v-if="item.fuente === 'EQUIPO_SEMILLA'">Dato de demostración</span>
        </div>

        <!-- Advertencia redactada por el servidor (RN-04, RN-09, EC-03) -->
        <p v-if="item.advertencia" class="mt-3 flex items-start gap-2 rounded-xl bg-warning-50 p-2.5 text-xs text-warning-800">
          <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span>{{ item.advertencia }}</span>
        </p>

        <!-- RF-10 y RF-19: solo sobre precios vigentes; nunca cambian la posición -->
        <AccionesPrecio v-if="item.vigente" :observacion-id="item.observacionId" :establecimiento="item.establecimiento.nombre" @confirmado="cargar" />
      </li>
    </ul>

    <div v-else-if="resultado" class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 px-6 py-10 text-center">
      <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-primary-50 text-primary-700">
        <SearchX class="h-7 w-7" aria-hidden="true" />
      </span>
      <p class="mt-3 font-display text-lg font-bold text-slate-900">Aún no hay precios de tiendas</p>
      <p class="mt-1 text-sm text-slate-600">Si lo viste en una góndola, cuéntale a la comunidad cuánto vale.</p>
      <router-link to="/registrar" class="mt-4 inline-flex h-11 items-center rounded-xl bg-accent-400 px-5 text-sm font-bold text-slate-900 shadow-tarjeta transition hover:bg-accent-300 active:scale-95">Reporta el primero</router-link>
    </div>

    <BloquePromociones v-if="resultado" :promociones="resultado.contenidoPromocional" />
    <ReferenciaMayorista :producto-id="productoId" />
  </section>
</template>
