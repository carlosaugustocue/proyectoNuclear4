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
    class="group block rounded-xl border border-slate-200 bg-white p-3 transition-shadow hover:shadow-md"
    :aria-label="etiqueta"
  >
    <ImagenProducto class="mb-2" :categoria="producto.categoria" :imagen="producto.imagen" con-credito />

    <p class="truncate text-sm font-semibold text-slate-800 group-hover:text-primary-700">
      {{ producto.nombre }}
    </p>
    <p class="text-xs text-slate-500">{{ producto.marca ?? producto.categoria }}</p>

    <template v-if="desde">
      <p class="mt-1.5 text-sm font-bold text-primary-700">
        <span class="font-normal text-slate-500">desde</span> {{ desde }}
      </p>
      <p v-if="resumen?.precioMin" class="text-[11px] text-slate-500">
        presentación más económica {{ formatearPesos(resumen.precioMin) }}
      </p>
      <div class="mt-1 flex items-center gap-1 text-xs text-slate-500">
        <Store class="h-3 w-3" aria-hidden="true" />
        {{ tiendas }} {{ tiendas === 1 ? 'tienda' : 'tiendas' }}
      </div>
    </template>
    <p v-else class="mt-1.5 text-xs text-slate-500">Sin precios vigentes · ¡sé el primero en reportar!</p>
  </router-link>
</template>
