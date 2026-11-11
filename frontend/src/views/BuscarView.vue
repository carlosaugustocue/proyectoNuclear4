<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Search } from 'lucide-vue-next'
import { productos, categorias } from '@/repositories/mock-data'
import ProductoCard from '@/components/ProductoCard.vue'
import CategoriaChip from '@/components/CategoriaChip.vue'

const route = useRoute()
const busqueda = ref((route.query.q as string) || '')
const categoriaActiva = ref('todos')

const resultados = computed(() => {
  let filtrados = productos

  if (categoriaActiva.value !== 'todos') {
    filtrados = filtrados.filter(p => p.categoria === categoriaActiva.value)
  }

  if (busqueda.value.trim()) {
    const q = busqueda.value.toLowerCase()
    filtrados = filtrados.filter(p =>
      p.nombre.toLowerCase().includes(q) || p.marca.toLowerCase().includes(q)
    )
  }

  return filtrados
})

const inputRef = ref<HTMLInputElement>()

onMounted(() => {
  inputRef.value?.focus()
})
</script>

<template>
  <section aria-labelledby="titulo-buscar">
    <h1 id="titulo-buscar" class="sr-only">Buscar productos</h1>

    <!-- Buscador -->
    <div class="relative mb-4">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
      <input
        ref="inputRef"
        v-model="busqueda"
        type="search"
        class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:outline-none"
        placeholder="Buscar productos..."
        aria-label="Buscar productos"
      />
    </div>

    <!-- Categorias -->
    <div class="mb-4 flex gap-2 overflow-x-auto pb-1 scrollbar-none">
      <CategoriaChip
        v-for="cat in categorias"
        :key="cat.id"
        :categoria="cat"
        :activa="categoriaActiva === cat.id"
        @click="categoriaActiva = cat.id"
      />
    </div>

    <!-- Resultados -->
    <p class="mb-3 text-xs font-medium text-slate-400">
      {{ resultados.length }} producto{{ resultados.length !== 1 ? 's' : '' }} encontrado{{ resultados.length !== 1 ? 's' : '' }}
    </p>

    <div v-if="resultados.length > 0" class="grid grid-cols-2 gap-3">
      <ProductoCard
        v-for="prod in resultados"
        :key="prod.id"
        :producto="prod"
      />
    </div>

    <div v-else class="py-12 text-center">
      <p class="text-sm text-slate-400">No se encontraron productos</p>
      <p class="mt-1 text-xs text-slate-300">Intenta con otro nombre o categoria</p>
    </div>
  </section>
</template>
