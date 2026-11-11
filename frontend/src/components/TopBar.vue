<script setup lang="ts">
import { MapPin } from 'lucide-vue-next'
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white px-4 py-3">
    <div class="mx-auto flex max-w-2xl items-center justify-between">
      <router-link to="/" class="text-lg font-bold text-primary-700">
        MercadoNorte
      </router-link>
      <div class="flex items-center gap-1.5 text-sm text-slate-500">
        <MapPin class="h-4 w-4" aria-hidden="true" />
        <span>Armenia, Quindio</span>
      </div>
    </div>
  </header>
</template>
