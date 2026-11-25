<script setup lang="ts">
import { MapPin } from 'lucide-vue-next'
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-surface/85 px-4 backdrop-blur-md supports-[backdrop-filter]:bg-surface/70">
    <div class="mx-auto flex h-14 max-w-2xl items-center justify-between">
      <router-link to="/" class="group flex items-center gap-2 rounded-lg" aria-label="MercadoNorte, inicio">
        <!-- Marca: etiqueta de precio con la «N» de Norte -->
        <svg viewBox="0 0 32 32" class="h-8 w-8 transition-transform duration-300 group-hover:-rotate-6" aria-hidden="true">
          <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11.6a2.5 2.5 0 0 1 1.77.73l7.4 7.4a2.5 2.5 0 0 1 0 3.54l-11.6 11.6a2.5 2.5 0 0 1-3.54 0l-7.4-7.4A2.5 2.5 0 0 1 4 18.1Z" class="fill-primary-700" />
          <circle cx="10" cy="10" r="2.2" class="fill-accent-400" />
          <path d="M11.5 21.5v-7l6 7v-7" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <span class="font-display text-xl font-bold tracking-tight text-slate-900">Mercado<span class="text-primary-700">Norte</span></span>
      </router-link>
      <p class="flex items-center gap-1 rounded-full bg-white/80 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">
        <MapPin class="h-3.5 w-3.5 text-primary-600" aria-hidden="true" />
        Armenia, Quindío
      </p>
    </div>
  </header>
</template>
