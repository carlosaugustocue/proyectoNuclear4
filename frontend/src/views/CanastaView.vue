<script setup lang="ts">
/*
 * Canasta comparada (Anexo 1, A1; RF-27 a RF-30). La canasta vive en el dispositivo (ADR-0018).
 * Los productos sin precio vigente se nombran en texto y nunca se suman (ADR-0019); el criterio
 * de orden siempre se muestra (RN-13). Accesible por teclado y lector de pantalla (EC-08).
 */
import { computed, ref } from 'vue'
import { ArrowUpDown, Minus, Search, ShoppingBasket, Trash2 } from 'lucide-vue-next'
import { buscarProductos, obtenerProducto } from '@/api/catalogo'
import { compararCanasta, type CriterioCanasta, type ResultadoCanasta } from '@/api/canasta'
import { mensajeDeError } from '@/api/client'
import { MAX_PRODUCTOS, unidadesPermitidas, useCanastaStore } from '@/stores/canasta'
import { formatearPesos } from '@/utils/formato'
import type { ProductoResumen } from '@/types'

const canasta = useCanastaStore()
const texto = ref('')
const sugerencias = ref<ProductoResumen[]>([])
const resultado = ref<ResultadoCanasta | null>(null)
const cargando = ref(false)
const error = ref<string | null>(null)
const aviso = ref<string | null>(null)

const nombres = computed(() => new Map(canasta.items.map((i) => [i.productoId, i.nombre])))

async function buscar() {
  error.value = null
  if (texto.value.trim().length < 2) {
    sugerencias.value = []
    return
  }
  try {
    sugerencias.value = (await buscarProductos({ q: texto.value.trim(), tamano: 8 })).items
  } catch (e) {
    error.value = mensajeDeError(e)
  }
}

async function agregar(producto: ProductoResumen) {
  try {
    const detalle = await obtenerProducto(producto.id)
    const unidadBase = detalle.presentaciones[0]?.unidadBase ?? 'UNIDAD'
    const agregado = canasta.agregar({ productoId: producto.id, nombre: producto.nombre, unidadBase })
    aviso.value = agregado
      ? `${producto.nombre} agregado a la canasta.`
      : canasta.llena ? `La canasta admite hasta ${MAX_PRODUCTOS} productos.` : `${producto.nombre} ya está en la canasta.`
    resultado.value = null
  } catch (e) {
    error.value = mensajeDeError(e)
  }
}

function quitar(productoId: string) {
  canasta.quitar(productoId)
  resultado.value = null
}

function vaciar() {
  canasta.vaciar()
  resultado.value = null
}

async function comparar(criterio: CriterioCanasta = canasta.criterio) {
  canasta.criterio = criterio
  cargando.value = true
  error.value = null
  try {
    resultado.value = await compararCanasta(canasta.solicitud())
  } catch (e) {
    error.value = mensajeDeError(e, 'No pudimos comparar la canasta.')
  } finally {
    cargando.value = false
  }
}
</script>

<template>
  <section aria-labelledby="titulo-canasta">
    <header class="mb-4 flex items-center gap-2">
      <ShoppingBasket class="h-6 w-6 text-primary-600" aria-hidden="true" />
      <div>
        <h1 id="titulo-canasta" class="text-lg font-bold text-slate-900">Mi canasta</h1>
        <p class="text-xs text-slate-500">Hasta {{ MAX_PRODUCTOS }} productos. Se guarda solo en este dispositivo.</p>
      </div>
    </header>

    <form class="mb-3" role="search" @submit.prevent="buscar">
      <label for="buscar-canasta" class="mb-1 block text-xs font-semibold text-slate-600">Agregar producto</label>
      <div class="flex gap-2">
        <input
          id="buscar-canasta" v-model="texto" type="search" placeholder="Ej.: arroz, leche…"
          class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
          :disabled="canasta.llena" @input="buscar"
        />
        <button type="submit" class="rounded-lg bg-slate-100 px-3 text-slate-600 hover:bg-slate-200" aria-label="Buscar producto">
          <Search class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </form>
    <ul v-if="sugerencias.length" class="mb-4 divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white" aria-label="Productos encontrados">
      <li v-for="p in sugerencias" :key="p.id" class="flex items-center justify-between px-3 py-2 text-sm">
        <span>{{ p.nombre }} <span class="text-xs text-slate-400">{{ p.marca ?? p.categoria }}</span></span>
        <button type="button" class="rounded-full px-3 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-50" @click="agregar(p)">Agregar</button>
      </li>
    </ul>
    <p class="sr-only" aria-live="polite">{{ aviso }}</p>

    <p v-if="!canasta.items.length" class="mb-4 rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
      Tu canasta está vacía. Busca productos para agregarlos.
    </p>
    <ul v-else class="mb-4 space-y-2" aria-label="Productos de la canasta">
      <li v-for="item in canasta.items" :key="item.productoId" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2">
        <span class="flex-1 text-sm font-medium text-slate-800">{{ item.nombre }}</span>
        <label class="sr-only" :for="`cant-${item.productoId}`">Cantidad de {{ item.nombre }}</label>
        <input
          :id="`cant-${item.productoId}`" :value="item.cantidad" inputmode="decimal" class="w-16 rounded border border-slate-300 px-2 py-1 text-right text-sm"
          @change="canasta.actualizar(item.productoId, { cantidad: ($event.target as HTMLInputElement).value })"
        />
        <label class="sr-only" :for="`uni-${item.productoId}`">Unidad de {{ item.nombre }}</label>
        <select
          :id="`uni-${item.productoId}`" :value="item.unidad" class="rounded border border-slate-300 px-1 py-1 text-sm"
          @change="canasta.actualizar(item.productoId, { unidad: ($event.target as HTMLSelectElement).value })"
        >
          <option v-for="u in unidadesPermitidas(item.unidadBase)" :key="u" :value="u">{{ u }}</option>
        </select>
        <button type="button" class="rounded p-1.5 text-slate-400 hover:bg-danger-50 hover:text-danger-600" :aria-label="`Quitar ${item.nombre}`" @click="quitar(item.productoId)">
          <Trash2 class="h-4 w-4" aria-hidden="true" />
        </button>
      </li>
    </ul>

    <div v-if="canasta.items.length" class="mb-4 flex gap-2">
      <button
        type="button" class="flex-1 rounded-lg bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
        :disabled="cargando" :aria-busy="cargando" @click="comparar()"
      >{{ cargando ? 'Comparando…' : 'Comparar canasta' }}</button>
      <button type="button" class="rounded-lg px-3 text-sm text-slate-500 hover:bg-slate-100" @click="vaciar">Vaciar</button>
    </div>

    <p v-if="error" class="mb-4 rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>

    <section v-if="resultado" aria-labelledby="titulo-resultado" aria-live="polite">
      <h2 id="titulo-resultado" class="sr-only">Resultado de la comparación</h2>
      <div class="mb-2 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2">
        <span id="etiqueta-criterio" class="flex items-center gap-1.5 text-xs text-slate-500"><ArrowUpDown class="h-3.5 w-3.5" aria-hidden="true" />Ordenar por:</span>
        <div class="flex gap-1" role="group" aria-labelledby="etiqueta-criterio">
          <button
            type="button" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
            :class="resultado.criterioOrden.campo === 'disponibilidad' ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
            :aria-pressed="resultado.criterioOrden.campo === 'disponibilidad'" @click="comparar('disponibilidad')"
          >Más productos</button>
          <button
            type="button" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
            :class="resultado.criterioOrden.campo === 'total' ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
            :aria-pressed="resultado.criterioOrden.campo === 'total'" @click="comparar('total')"
          >Menor total</button>
        </div>
      </div>
      <p class="mb-3 text-xs leading-relaxed text-slate-500">{{ resultado.criterioOrden.descripcion }}</p>

      <p v-if="!resultado.establecimientos.length" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
        Ningún establecimiento tiene precios vigentes para los productos de tu canasta.
      </p>
      <ol class="space-y-3">
        <li v-for="e in resultado.establecimientos" :key="e.establecimiento.id" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="text-xs text-slate-400">#{{ e.posicion }}</p>
              <p class="font-semibold text-slate-900">{{ e.establecimiento.nombre }}</p>
            </div>
            <div class="text-right">
              <p class="text-lg font-bold text-slate-900">{{ formatearPesos(e.total) }}</p>
              <p class="text-xs" :class="e.completa ? 'text-success-700' : 'text-warning-700'">
                {{ e.disponibles }} de {{ e.totalProductos }} productos{{ e.completa ? '' : ' · total parcial' }}
              </p>
            </div>
          </div>
          <p v-if="!e.completa" class="mt-2 flex items-start gap-1.5 rounded-lg bg-warning-50 p-2 text-xs text-warning-800">
            <Minus class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            <span>Sin precio vigente (no se suma al total): {{ e.faltantes.map((id) => nombres.get(id) ?? id).join(', ') }}.</span>
          </p>
        </li>
      </ol>
      <p class="mt-3 text-[11px] text-slate-400">Las promociones nunca cambian el total ni el orden; se muestran aparte cuando existen.</p>
    </section>
  </section>
</template>
