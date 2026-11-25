<script setup lang="ts">
import { computed } from 'vue'
import { Store } from 'lucide-vue-next'
import ImagenProducto from './ImagenProducto.vue'
import type { ProductoResumen, ResumenProducto } from '@/types'
import { formatearPesos, precioPorUnidadTexto } from '@/utils/formato'

const props = defineProps<{
  producto: ProductoResumen
  resumen?: ResumenProducto
}>()

const desde = computed(() =>
  props.resumen?.precioPorUnidadMin && props.resumen.unidadBase
    ? precioPorUnidadTexto(props.resumen.precioPorUnidadMin, props.resumen.unidadBase)
    : null,
)
/* «$5.450/kg» → cifra «$5.450» y unidad «/kg», para dar jerarquía al número (ui-ux: el precio por unidad manda). */
const partes = computed(() => {
  const [cifra, unidad] = (desde.value ?? '').split('/')
  return { cifra, unidad: unidad ? `/${unidad}` : '' }
})
const tiendas = computed(() => props.resumen?.establecimientos ?? 0)
const etiqueta = computed(() =>
  desde.value
    ? `${props.producto.nombre}: desde ${desde.value} en ${tiendas.value} tiendas`
    : `${props.producto.nombre}: sin precios vigentes`,
)
</script>

<template>
  <router-link
    :to="{ name: 'comparar', params: { productoId: producto.id } }"
    class="group flex h-full flex-col rounded-2xl bg-white p-2.5 shadow-tarjeta ring-1 ring-slate-200/70 transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-elevada hover:ring-primary-200 active:scale-[0.98]"
    :aria-label="etiqueta"
  >
    <div class="relative">
      <ImagenProducto :categoria="producto.categoria" :imagen="producto.imagen" />
      <span
        v-if="desde"
        class="absolute left-1.5 top-1.5 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[11px] font-semibold text-slate-700 shadow-sm"
      >
        <Store class="h-3 w-3 text-primary-600" aria-hidden="true" />
        {{ tiendas }} {{ tiendas === 1 ? 'tienda' : 'tiendas' }}
      </span>
    </div>

    <div class="mt-2.5 flex flex-1 flex-col px-1 pb-0.5">
      <p class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 group-hover:text-primary-700">
        {{ producto.nombre }}
      </p>
      <p class="truncate text-xs text-slate-500">{{ producto.marca ?? producto.categoria }}</p>

      <div v-if="desde" class="mt-auto pt-2">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">desde</p>
        <p class="cifra text-xl font-bold leading-none text-slate-900">
          {{ partes.cifra }}<span class="text-sm font-semibold text-slate-500">{{ partes.unidad }}</span>
        </p>
        <p v-if="resumen?.precioMin" class="mt-1 text-[11px] text-slate-500">
          o {{ formatearPesos(resumen.precioMin) }} la presentación más económica
        </p>
      </div>
      <p v-else class="mt-auto pt-2 text-xs font-medium text-primary-700">Sin precios aún · reporta el primero</p>
    </div>
  </router-link>
</template>
