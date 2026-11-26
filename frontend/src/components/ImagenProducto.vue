<script setup lang="ts">
/*
 * Foto del producto (licencia libre, con crédito visible) o, si no hay o no carga, la ilustración de
 * su categoría. La imagen es decorativa: el nombre del producto ya está en el texto (alt vacío).
 */
import { computed, ref } from 'vue'
import { ilustracionDe, urlSegura } from '@/utils/imagenProducto'
import { ICONOS_CATEGORIA as ICONOS } from '@/utils/iconosCategoria'
import type { ImagenProducto } from '@/types'

const props = withDefaults(
  defineProps<{ categoria: string; imagen?: ImagenProducto | null; tamano?: 'sm' | 'md' | 'lg'; conCredito?: boolean }>(),
  { imagen: null, tamano: 'md', conCredito: false },
)

const CAJA = { sm: 'h-10 w-10 rounded-xl', md: 'aspect-[4/3] w-full rounded-xl', lg: 'h-16 w-16 rounded-2xl' }
const ICONO = { sm: 'h-5 w-5', md: 'h-11 w-11', lg: 'h-8 w-8' }

const fallo = ref(false)
const url = computed(() => (fallo.value ? null : urlSegura(props.imagen?.url)))
const ilustracion = computed(() => ilustracionDe(props.categoria))
</script>

<template>
  <figure class="m-0 shrink-0">
    <div
      class="flex items-center justify-center overflow-hidden"
      :class="[CAJA[tamano], url ? 'bg-slate-100' : ilustracion.clases]"
    >
      <img
        v-if="url"
        :src="url"
        alt=""
        loading="lazy"
        decoding="async"
        referrerpolicy="no-referrer"
        class="h-full w-full rounded-[inherit] object-cover"
        @error="fallo = true"
      />
      <component :is="ICONOS[ilustracion.icono]" v-else :class="ICONO[tamano]" aria-hidden="true" stroke-width="1.6" />
    </div>
    <figcaption v-if="conCredito && url && imagen?.credito" class="mt-0.5 text-[10px] leading-tight text-slate-500">
      {{ imagen.credito }}
    </figcaption>
  </figure>
</template>
