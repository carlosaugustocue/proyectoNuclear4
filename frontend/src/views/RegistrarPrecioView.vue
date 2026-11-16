<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { CheckCircle, CloudOff, MapPin, Store, Package, DollarSign, ChevronRight, Search } from 'lucide-vue-next'
import { buscarEstablecimientos } from '@/api/establecimientos'
import { buscarProductos, obtenerProducto } from '@/api/catalogo'
import { mensajeDeError } from '@/api/client'
import { useReportesStore, type DesenlaceReporte } from '@/stores/reportes'
import { useUbicacion } from '@/composables/useUbicacion'
import { describirPresentacion, TIPO_ESTABLECIMIENTO } from '@/utils/catalogo'
import { formatearDistancia, formatearPesos } from '@/utils/formato'
import type { Establecimiento, ProductoDetalle, ProductoResumen } from '@/types'

const TOTAL_PASOS = 4
const PRECIO_VALIDO = /^[0-9]+(\.[0-9]{1,2})?$/

const reportes = useReportesStore()
const { coordenadas, solicitar } = useUbicacion()

const paso = ref(1)
const desenlace = ref<DesenlaceReporte | null>(null)
const enviando = ref(false)
const error = ref<string | null>(null)

const establecimientos = ref<Establecimiento[]>([])
const productos = ref<ProductoResumen[]>([])
const detalle = ref<ProductoDetalle | null>(null)
const filtroProducto = ref('')

const form = reactive({ establecimientoId: '', productoId: '', presentacionId: '', precio: '' })

const establecimiento = computed(() => establecimientos.value.find((e) => e.id === form.establecimientoId))
const presentacion = computed(() => detalle.value?.presentaciones.find((p) => p.id === form.presentacionId))
const precioNormalizado = computed(() => form.precio.replace(/[.\s$]/g, '').replace(',', '.'))
const precioValido = computed(() => PRECIO_VALIDO.test(precioNormalizado.value) && Number(precioNormalizado.value) > 0)

const puedeAvanzar = computed(() => {
  if (paso.value === 1) return !!form.establecimientoId
  if (paso.value === 2) return !!form.presentacionId
  if (paso.value === 3) return precioValido.value
  return true
})

async function cargarEstablecimientos() {
  try {
    const cerca = coordenadas.value ? { ...coordenadas.value, radioKm: 2 } : {}
    establecimientos.value = (await buscarEstablecimientos({ ...cerca, tamano: 30 })).items
  } catch (e) {
    error.value = mensajeDeError(e)
  }
}

async function cargarProductos() {
  try {
    productos.value = (await buscarProductos({ q: filtroProducto.value.trim() || undefined, tamano: 30 })).items
  } catch (e) {
    error.value = mensajeDeError(e)
  }
}

async function elegirProducto(id: string) {
  form.productoId = id
  form.presentacionId = ''
  detalle.value = await obtenerProducto(id)
  if (detalle.value.presentaciones.length === 1) form.presentacionId = detalle.value.presentaciones[0]!.id
}

async function usarUbicacion() {
  await solicitar()
  await cargarEstablecimientos()
}

async function confirmar() {
  if (!establecimiento.value || !presentacion.value || !detalle.value) return
  enviando.value = true
  error.value = null
  try {
    desenlace.value = await reportes.reportar(
      {
        presentacionId: form.presentacionId,
        establecimientoId: form.establecimientoId,
        precio: precioNormalizado.value,
        observadoEn: new Date().toISOString(),
      },
      {
        producto: `${detalle.value.nombre} ${describirPresentacion(presentacion.value)}`,
        establecimiento: establecimiento.value.nombre,
      },
    )
    if (desenlace.value === 'rechazado') {
      error.value = 'El servidor rechazó el reporte. Revisa el precio e inténtalo de nuevo.'
      desenlace.value = null
      paso.value = 3
    }
  } finally {
    enviando.value = false
  }
}

function reiniciar() {
  Object.assign(form, { productoId: '', presentacionId: '', precio: '' }) // conserva la tienda
  detalle.value = null
  desenlace.value = null
  paso.value = 2
}

let temporizador: ReturnType<typeof setTimeout> | undefined
watch(filtroProducto, () => {
  clearTimeout(temporizador)
  temporizador = setTimeout(cargarProductos, 250)
})

onMounted(() => Promise.all([cargarEstablecimientos(), cargarProductos()]))
</script>

<template>
  <section aria-labelledby="titulo-registrar">
    <h1 id="titulo-registrar" class="mb-1 text-lg font-bold text-slate-900">Registrar precio</h1>
    <p class="mb-4 text-xs text-slate-500">Reporta el precio que ves en la tienda. Funciona aunque no tengas señal.</p>

    <div v-if="!desenlace" class="mb-6 flex gap-1" role="progressbar" :aria-valuenow="paso" aria-valuemin="1" :aria-valuemax="TOTAL_PASOS" :aria-label="`Paso ${paso} de ${TOTAL_PASOS}`">
      <div v-for="i in TOTAL_PASOS" :key="i" class="h-1 flex-1 rounded-full transition-colors" :class="i <= paso ? 'bg-primary-600' : 'bg-slate-200'" />
    </div>

    <p v-if="error" class="mb-4 rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>

    <div v-if="desenlace" class="py-8 text-center" role="status">
      <CheckCircle v-if="desenlace === 'enviado'" class="mx-auto mb-3 h-14 w-14 text-success-500" aria-hidden="true" />
      <CloudOff v-else class="mx-auto mb-3 h-14 w-14 text-warning-500" aria-hidden="true" />
      <h2 class="text-lg font-bold text-slate-900">{{ desenlace === 'enviado' ? 'Precio registrado' : 'Guardado en tu teléfono' }}</h2>
      <p class="mt-1 text-sm text-slate-500">
        {{ desenlace === 'enviado'
          ? 'Gracias por tu aporte. Lo verificaremos con otros reportes en unos segundos.'
          : 'No hay conexión. Lo enviaremos automáticamente cuando vuelva la señal, sin duplicarlo.' }}
      </p>
      <button type="button" class="mt-6 w-full rounded-xl bg-primary-600 py-3 text-sm font-semibold text-white hover:bg-primary-700" @click="reiniciar">
        Registrar otro precio en {{ establecimiento?.nombre }}
      </button>
    </div>

    <fieldset v-else-if="paso === 1">
      <legend class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <Store class="h-4 w-4 text-primary-600" aria-hidden="true" /> ¿Dónde estás comprando?
      </legend>
      <button type="button" class="mb-3 inline-flex items-center gap-1 text-xs font-semibold text-primary-700 underline" @click="usarUbicacion">
        <MapPin class="h-3.5 w-3.5" aria-hidden="true" /> Mostrar tiendas cerca de mí
      </button>
      <ul class="space-y-2" role="radiogroup" aria-label="Seleccionar establecimiento">
        <li v-for="est in establecimientos" :key="est.id">
          <button
            type="button"
            class="flex w-full items-center justify-between rounded-xl border px-4 py-3 text-left text-sm transition-colors"
            :class="form.establecimientoId === est.id ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
            role="radio"
            :aria-checked="form.establecimientoId === est.id"
            @click="form.establecimientoId = est.id"
          >
            <div>
              <p class="font-semibold">{{ est.nombre }}</p>
              <p class="text-xs text-slate-500">{{ TIPO_ESTABLECIMIENTO[est.tipo] }} · {{ est.direccion }}</p>
            </div>
            <span v-if="est.distanciaKm !== undefined" class="text-xs text-slate-500">{{ formatearDistancia(est.distanciaKm) }}</span>
          </button>
        </li>
      </ul>
    </fieldset>

    <fieldset v-else-if="paso === 2">
      <legend class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <Package class="h-4 w-4 text-primary-600" aria-hidden="true" /> ¿Qué producto estás viendo?
      </legend>
      <div class="relative mb-3">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <input v-model="filtroProducto" type="search" class="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm focus:border-primary-500 focus:outline-none" placeholder="Filtrar productos" aria-label="Filtrar productos" />
      </div>
      <ul class="space-y-2" role="radiogroup" aria-label="Seleccionar producto">
        <li v-for="prod in productos" :key="prod.id">
          <button
            type="button"
            class="w-full rounded-xl border px-4 py-3 text-left text-sm transition-colors"
            :class="form.productoId === prod.id ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
            role="radio"
            :aria-checked="form.productoId === prod.id"
            @click="elegirProducto(prod.id)"
          >
            <p class="font-semibold">{{ prod.nombre }}</p>
            <p class="text-xs text-slate-500">{{ prod.marca ?? prod.categoria }}</p>
          </button>
          <div v-if="form.productoId === prod.id && detalle" class="mt-2 flex flex-wrap gap-2 pl-2" role="radiogroup" aria-label="Presentación">
            <button
              v-for="p in detalle.presentaciones"
              :key="p.id"
              type="button"
              role="radio"
              :aria-checked="form.presentacionId === p.id"
              class="rounded-full border px-3 py-1 text-xs font-semibold"
              :class="form.presentacionId === p.id ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-300 bg-white text-slate-700'"
              @click="form.presentacionId = p.id"
            >{{ describirPresentacion(p) }}</button>
          </div>
        </li>
      </ul>
    </fieldset>

    <div v-else-if="paso === 3">
      <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <DollarSign class="h-4 w-4 text-primary-600" aria-hidden="true" /> ¿Cuál es el precio?
      </p>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <label for="precio" class="mb-1 block text-xs font-medium text-slate-500">Precio en pesos de {{ presentacion && describirPresentacion(presentacion) }}</label>
        <input
          id="precio"
          v-model="form.precio"
          type="text"
          inputmode="numeric"
          autocomplete="off"
          class="w-full border-b-2 border-slate-200 pb-2 text-3xl font-bold text-slate-900 focus:border-primary-500 focus:outline-none"
          placeholder="2990"
          :aria-invalid="form.precio !== '' && !precioValido"
          aria-describedby="ayuda-precio"
        />
        <p id="ayuda-precio" class="mt-2 text-xs" :class="form.precio !== '' && !precioValido ? 'text-danger-700' : 'text-slate-500'">
          {{ form.precio !== '' && !precioValido ? 'Escribe solo números, por ejemplo 2990.' : 'El precio total que marca la etiqueta.' }}
        </p>
      </div>
    </div>

    <div v-else-if="paso === 4">
      <p class="mb-3 text-sm font-semibold text-slate-700">Confirma tu reporte</p>
      <dl class="space-y-2 rounded-xl border border-slate-200 bg-white p-4 text-sm">
        <div class="flex justify-between"><dt class="text-slate-500">Establecimiento</dt><dd class="font-semibold text-slate-800">{{ establecimiento?.nombre }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Producto</dt><dd class="font-semibold text-slate-800">{{ detalle?.nombre }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Presentación</dt><dd class="font-semibold text-slate-800">{{ presentacion && describirPresentacion(presentacion) }}</dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Precio</dt><dd class="font-semibold text-slate-800">{{ formatearPesos(precioNormalizado) }}</dd></div>
      </dl>
    </div>

    <div v-if="!desenlace" class="mt-6 flex gap-3">
      <button v-if="paso > 1" type="button" class="flex-1 rounded-xl border border-slate-200 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="paso--">Atrás</button>
      <button
        v-if="paso < TOTAL_PASOS"
        type="button"
        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl py-3 text-sm font-semibold text-white transition-colors"
        :class="puedeAvanzar ? 'bg-primary-600 hover:bg-primary-700' : 'cursor-not-allowed bg-slate-400'"
        :disabled="!puedeAvanzar"
        @click="paso++"
      >
        Siguiente <ChevronRight class="h-4 w-4" aria-hidden="true" />
      </button>
      <button
        v-else
        type="button"
        class="flex-1 rounded-xl bg-success-700 py-3 text-sm font-semibold text-white hover:bg-success-500"
        :disabled="enviando"
        :aria-busy="enviando"
        @click="confirmar"
      >{{ enviando ? 'Registrando…' : 'Confirmar y registrar' }}</button>
    </div>
  </section>
</template>
