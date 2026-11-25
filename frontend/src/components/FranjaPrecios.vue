<script setup lang="ts">
import type { RangoPrecios } from '@/utils/rangoComparacion'

/*
 * Franja de precios: cada punto es una tienda vigente, ubicada entre el menor y el mayor precio por
 * unidad. Es una ayuda visual del criterio declarado (RN-10); el detalle accesible está en la lista.
 */
defineProps<{ rango: RangoPrecios }>()
</script>

<template>
  <figure class="m-0">
    <div class="relative h-8" role="img" :aria-label="`${rango.tiendas} tiendas con precio vigente, de ${rango.minimo} a ${rango.maximo}`">
      <div class="absolute inset-x-0 top-1/2 h-2 -translate-y-1/2 rounded-full bg-gradient-to-r from-primary-400 via-primary-200/70 to-accent-300/80" />
      <span
        v-for="p in rango.puntos"
        :key="p.id"
        class="absolute top-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-[3px] border-primary-950 shadow"
        :class="p.posicion === 0 ? 'z-10 h-5 w-5 bg-white' : 'bg-primary-100'"
        :style="{ left: `calc(${p.posicion * 100}% * 0.94 + 3%)` }"
      />
    </div>
    <figcaption class="mt-1.5 flex justify-between text-xs text-primary-200">
      <span><span class="cifra font-semibold text-white">{{ rango.minimo }}</span> la más barata</span>
      <span class="text-right">la más cara <span class="cifra font-semibold text-white">{{ rango.maximo }}</span></span>
    </figcaption>
  </figure>
</template>
