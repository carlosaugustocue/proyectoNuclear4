<script setup lang="ts">
/*
 * Contenido promocional (EC-06, RN-16, ADR-0023): visualmente separado del resultado y
 * rotulado como promoción. Nunca cambia el orden ni el total; por eso va DESPUÉS de la lista.
 */
import { Megaphone } from 'lucide-vue-next'
import type { PromocionPublica } from '@/types'

const props = defineProps<{
  promociones: PromocionPublica[]
  /** Nombres de producto por id (en la canasta hay varios productos). */
  nombresProducto?: Map<string, string>
}>()

function hasta(iso: string): string {
  return new Date(iso).toLocaleDateString('es-CO', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <aside v-if="props.promociones.length" class="mt-5 rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-3" aria-labelledby="titulo-promociones">
    <h2 id="titulo-promociones" class="flex items-center gap-1.5 text-sm font-semibold text-amber-900">
      <Megaphone class="h-4 w-4" aria-hidden="true" /> Promociones publicadas por los comercios
    </h2>
    <p class="mb-2 text-[11px] text-amber-800">Contenido promocional: no cambia el orden ni el total de la comparación.</p>
    <ul class="space-y-1.5">
      <li
        v-for="p in props.promociones"
        :key="`${p.establecimiento.id}-${p.productoId}-${p.descripcion}`"
        class="flex items-baseline justify-between gap-2 rounded-lg bg-white px-2.5 py-1.5 text-xs"
      >
        <span>
          <span class="font-semibold text-slate-800">{{ p.descripcion }}</span>
          <span class="text-slate-500"> · {{ p.establecimiento.nombre }}</span>
          <span v-if="props.nombresProducto?.get(p.productoId)" class="text-slate-500"> · {{ props.nombresProducto.get(p.productoId) }}</span>
        </span>
        <span class="shrink-0 text-slate-500">hasta {{ hasta(p.vigenteHasta) }}</span>
      </li>
    </ul>
  </aside>
</template>
