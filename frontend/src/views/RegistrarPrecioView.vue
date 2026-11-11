<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import { CheckCircle, MapPin, Store, Package, DollarSign, ChevronRight } from 'lucide-vue-next'
import { establecimientos, productos } from '@/repositories/mock-data'

const paso = ref(1)
const totalPasos = 5
const exito = ref(false)
const cargando = ref(false)

const form = reactive({
  establecimientoId: '',
  productoId: '',
  monto: '',
  cantidad: '1',
  unidad: 'kg',
})

const establecimientoSeleccionado = computed(() =>
  establecimientos.find(e => e.id === form.establecimientoId)
)

const productoSeleccionado = computed(() =>
  productos.find(p => p.id === form.productoId)
)

const puedeAvanzar = computed(() => {
  if (paso.value === 1) return !!form.establecimientoId
  if (paso.value === 2) return !!form.productoId
  if (paso.value === 3) return !!form.monto && Number(form.monto) > 0
  if (paso.value === 4) return true
  return true
})

function siguiente() {
  if (paso.value < totalPasos) {
    paso.value++
  }
}

function anterior() {
  if (paso.value > 1) {
    paso.value--
  }
}

async function confirmar() {
  cargando.value = true
  // Simula envio al backend
  await new Promise(r => setTimeout(r, 600))
  cargando.value = false
  exito.value = true
}

function reiniciar() {
  form.establecimientoId = ''
  form.productoId = ''
  form.monto = ''
  form.cantidad = '1'
  form.unidad = 'kg'
  paso.value = 1
  exito.value = false
}
</script>

<template>
  <section aria-labelledby="titulo-registrar">
    <h1 id="titulo-registrar" class="mb-1 text-lg font-bold text-slate-900">Registrar precio</h1>
    <p class="mb-4 text-xs text-slate-400">Reporta el precio que ves en la tienda.</p>

    <!-- Barra de progreso -->
    <div v-if="!exito" class="mb-6 flex gap-1" aria-label="Progreso del registro">
      <div
        v-for="i in totalPasos"
        :key="i"
        class="h-1 flex-1 rounded-full transition-colors"
        :class="i <= paso ? 'bg-primary-600' : 'bg-slate-200'"
      />
    </div>

    <!-- Exito -->
    <div v-if="exito" class="py-8 text-center">
      <CheckCircle class="mx-auto mb-3 h-14 w-14 text-success-500" aria-hidden="true" />
      <h2 class="text-lg font-bold text-slate-900">Precio registrado</h2>
      <p class="mt-1 text-sm text-slate-500">Gracias por tu aporte. Otros usuarios podran corroborarlo.</p>
      <button
        class="mt-6 w-full rounded-xl bg-primary-600 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-700"
        @click="reiniciar"
      >
        Registrar otro precio
      </button>
    </div>

    <!-- Paso 1: Establecimiento -->
    <div v-else-if="paso === 1">
      <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <Store class="h-4 w-4 text-primary-600" aria-hidden="true" />
        Donde estas comprando?
      </div>
      <ul class="space-y-2" role="radiogroup" aria-label="Seleccionar establecimiento">
        <li v-for="est in establecimientos" :key="est.id">
          <button
            class="flex w-full items-center justify-between rounded-xl border px-4 py-3 text-left text-sm transition-colors"
            :class="form.establecimientoId === est.id
              ? 'border-primary-500 bg-primary-50 text-primary-700'
              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
            role="radio"
            :aria-checked="form.establecimientoId === est.id"
            @click="form.establecimientoId = est.id"
          >
            <div>
              <p class="font-semibold">{{ est.nombre }}</p>
              <p class="text-xs text-slate-400">{{ est.direccion }}</p>
            </div>
            <div v-if="est.distanciaMetros" class="flex items-center gap-1 text-xs text-slate-400">
              <MapPin class="h-3 w-3" aria-hidden="true" />
              {{ est.distanciaMetros < 1000 ? `${est.distanciaMetros} m` : `${(est.distanciaMetros / 1000).toFixed(1)} km` }}
            </div>
          </button>
        </li>
      </ul>
    </div>

    <!-- Paso 2: Producto -->
    <div v-else-if="paso === 2">
      <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <Package class="h-4 w-4 text-primary-600" aria-hidden="true" />
        Que producto estas viendo?
      </div>
      <ul class="space-y-2" role="radiogroup" aria-label="Seleccionar producto">
        <li v-for="prod in productos" :key="prod.id">
          <button
            class="flex w-full items-center justify-between rounded-xl border px-4 py-3 text-left text-sm transition-colors"
            :class="form.productoId === prod.id
              ? 'border-primary-500 bg-primary-50 text-primary-700'
              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
            role="radio"
            :aria-checked="form.productoId === prod.id"
            @click="form.productoId = prod.id"
          >
            <div>
              <p class="font-semibold">{{ prod.nombre }}</p>
              <p class="text-xs text-slate-400">{{ prod.marca }} · {{ prod.presentacion }}</p>
            </div>
          </button>
        </li>
      </ul>
    </div>

    <!-- Paso 3: Precio -->
    <div v-else-if="paso === 3">
      <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
        <DollarSign class="h-4 w-4 text-primary-600" aria-hidden="true" />
        Cual es el precio?
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <label for="monto" class="mb-1 block text-xs font-medium text-slate-500">Precio en pesos ($)</label>
        <input
          id="monto"
          v-model="form.monto"
          type="number"
          inputmode="numeric"
          min="1"
          step="1"
          class="w-full border-b-2 border-slate-200 pb-2 text-3xl font-bold text-slate-900 focus:border-primary-500 focus:outline-none"
          placeholder="2.990"
          autofocus
        />
        <div class="mt-4 flex gap-3">
          <div class="flex-1">
            <label for="cantidad" class="mb-1 block text-xs font-medium text-slate-500">Cantidad</label>
            <input
              id="cantidad"
              v-model="form.cantidad"
              type="text"
              class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
              placeholder="500"
            />
          </div>
          <div class="w-24">
            <label for="unidad" class="mb-1 block text-xs font-medium text-slate-500">Unidad</label>
            <select
              id="unidad"
              v-model="form.unidad"
              class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
            >
              <option value="g">g</option>
              <option value="kg">kg</option>
              <option value="mL">mL</option>
              <option value="L">L</option>
              <option value="unidad">unidad</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Paso 4: Confirmacion -->
    <div v-else-if="paso === 4">
      <p class="mb-3 text-sm font-semibold text-slate-700">Confirma tu reporte</p>
      <div class="space-y-2 rounded-xl border border-slate-200 bg-white p-4 text-sm">
        <div class="flex justify-between">
          <span class="text-slate-400">Establecimiento</span>
          <span class="font-semibold text-slate-800">{{ establecimientoSeleccionado?.nombre }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Producto</span>
          <span class="font-semibold text-slate-800">{{ productoSeleccionado?.nombre }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Precio</span>
          <span class="font-semibold text-slate-800">${{ Number(form.monto).toLocaleString('es-CO') }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-400">Presentacion</span>
          <span class="font-semibold text-slate-800">{{ form.cantidad }} {{ form.unidad }}</span>
        </div>
      </div>
    </div>

    <!-- Navegacion de pasos -->
    <div v-if="!exito" class="mt-6 flex gap-3">
      <button
        v-if="paso > 1"
        class="flex-1 rounded-xl border border-slate-200 py-3 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50"
        @click="anterior"
      >
        Atras
      </button>
      <button
        v-if="paso < 4"
        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl py-3 text-sm font-semibold text-white transition-colors"
        :class="puedeAvanzar ? 'bg-primary-600 hover:bg-primary-700' : 'bg-slate-300 cursor-not-allowed'"
        :disabled="!puedeAvanzar"
        @click="siguiente"
      >
        Siguiente
        <ChevronRight class="h-4 w-4" aria-hidden="true" />
      </button>
      <button
        v-if="paso === 4"
        class="flex-1 rounded-xl bg-success-500 py-3 text-sm font-semibold text-white transition-colors hover:bg-success-700"
        :disabled="cargando"
        :aria-busy="cargando"
        @click="confirmar"
      >
        {{ cargando ? 'Registrando...' : 'Confirmar y registrar' }}
      </button>
    </div>
  </section>
</template>
