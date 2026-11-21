<script setup lang="ts">
/*
 * Foto del producto (licencia libre, con crédito visible) o, si no hay o no carga, la ilustración de
 * su categoría. La imagen es decorativa: el nombre del producto ya está en el texto (alt vacío).
 */
import { computed, ref, type Component } from 'vue'
import { Wheat, Milk, Droplet, CupSoda, Apple, SprayCan, ShoppingBasket } from 'lucide-vue-next'
import { ilustracionDe, urlSegura, type IconoCategoria } from '@/utils/imagenProducto'
import type { ImagenProducto } from '@/types'

const props = withDefaults(
  defineProps<{ categoria: string; imagen?: ImagenProducto | null; tamano?: 'sm' | 'md' | 'lg'; conCredito?: boolean }>(),
  { imagen: null, tamano: 'md', conCredito: false },
)

const ICONOS: Record<IconoCategoria, Component> = {
  granos: Wheat, lacteos: Milk, aceites: Droplet, bebidas: CupSoda, frutas: Apple, aseo: SprayCan, generico: ShoppingBasket,
}
const CAJA = { sm: 'h-10 w-10 rounded-lg', md: 'h-20 w-full rounded-lg', lg: 'h-16 w-16 rounded-xl' }
const ICONO = { sm: 'h-5 w-5', md: 'h-9 w-9', lg: 'h-8 w-8' }

const fallo = ref(false)
const url = computed(() => (fallo.value ? null : urlSegura(props.imagen?.url)))
const ilustracion = computed(() => ilustracionDe(props.categoria))
</script>

<template>
  <figure class="m-0 shrink-0">
    <div
      class="flex items-center justify-center overflow-hidden"
      :class="[CAJA[tamano], url ? 'bg-white ring-1 ring-slate-100' : ilustracion.clases]"
    >
      <img
        v-if="url"
        :src="url"
        alt=""
        loading="lazy"
        decoding="async"
        referrerpolicy="no-referrer"
        class="h-full w-full object-contain p-1"
        @error="fallo = true"
      />
      <component :is="ICONOS[ilustracion.icono]" v-else :class="ICONO[tamano]" aria-hidden="true" stroke-width="1.6" />
    </div>
    <figcaption v-if="conCredito && url && imagen?.credito" class="mt-0.5 text-[10px] leading-tight text-slate-400">
      {{ imagen.credito }}
    </figcaption>
  </figure>
</template>
