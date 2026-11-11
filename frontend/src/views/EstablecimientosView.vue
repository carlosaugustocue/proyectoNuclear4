<script setup lang="ts">
import { MapPin, Store } from 'lucide-vue-next'
import { establecimientos } from '@/repositories/mock-data'

const tipoLabel: Record<string, string> = {
  supermercado: 'Supermercado',
  descuento: 'Tienda de descuento',
  tienda_barrio: 'Tienda de barrio',
  minimercado: 'Minimercado',
}
</script>

<template>
  <section aria-labelledby="titulo-establecimientos">
    <h1 id="titulo-establecimientos" class="mb-1 text-lg font-bold text-slate-900">Establecimientos</h1>
    <p class="mb-4 text-xs text-slate-400">Tiendas y supermercados cerca de ti en Armenia.</p>

    <ul class="space-y-2">
      <li
        v-for="est in establecimientos"
        :key="est.id"
        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4"
      >
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50">
          <Store class="h-5 w-5 text-primary-600" aria-hidden="true" />
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-slate-800">{{ est.nombre }}</p>
          <p class="text-xs text-slate-400">{{ tipoLabel[est.tipo] ?? est.tipo }}</p>
          <p class="text-xs text-slate-400">{{ est.direccion }}</p>
        </div>
        <div v-if="est.distanciaMetros" class="flex items-center gap-1 text-xs text-slate-400">
          <MapPin class="h-3 w-3" aria-hidden="true" />
          {{ est.distanciaMetros < 1000 ? `${est.distanciaMetros} m` : `${(est.distanciaMetros / 1000).toFixed(1)} km` }}
        </div>
      </li>
    </ul>
  </section>
</template>
