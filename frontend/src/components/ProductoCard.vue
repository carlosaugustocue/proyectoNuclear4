<script setup lang="ts">
import type { Producto } from '@/types'
import { Store } from 'lucide-vue-next'

defineProps<{
  producto: Producto
}>()
</script>

<template>
  <router-link
    :to="{ name: 'comparar', params: { productoId: producto.id } }"
    class="group block rounded-xl border border-slate-200 bg-white p-3 transition-shadow hover:shadow-md"
    :aria-label="`${producto.nombre} — ${producto.precioMin} a ${producto.precioMax} en ${producto.tiendas} tiendas`"
  >
    <!-- Placeholder de imagen -->
    <div class="mb-2 flex h-20 items-center justify-center rounded-lg bg-slate-50 text-2xl">
      🛒
    </div>

    <p class="truncate text-sm font-semibold text-slate-800 group-hover:text-primary-700">
      {{ producto.nombre }}
    </p>
    <p class="text-xs text-slate-400">{{ producto.presentacion }}</p>

    <p class="mt-1.5 text-sm font-bold text-primary-600">
      {{ producto.precioMin }}
      <span v-if="producto.precioMin !== producto.precioMax" class="font-normal text-slate-400">
        – {{ producto.precioMax }}
      </span>
    </p>

    <div class="mt-1 flex items-center gap-1 text-xs text-slate-400">
      <Store class="h-3 w-3" aria-hidden="true" />
      {{ producto.tiendas }} tiendas
    </div>
  </router-link>
</template>
