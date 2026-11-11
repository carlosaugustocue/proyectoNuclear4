<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Search, TrendingDown, ChevronRight } from 'lucide-vue-next'
import { categorias, productos } from '@/repositories/mock-data'
import ProductoCard from '@/components/ProductoCard.vue'
import CategoriaChip from '@/components/CategoriaChip.vue'

const router = useRouter()
const busqueda = ref('')
const categoriaActiva = ref('todos')

const productosDestacados = productos.slice(0, 4)

function irABuscar() {
  if (busqueda.value.trim()) {
    router.push({ name: 'buscar', query: { q: busqueda.value } })
  } else {
    router.push({ name: 'buscar' })
  }
}
</script>

<template>
  <section aria-labelledby="titulo-inicio">
    <!-- Hero -->
    <div class="mb-6 text-center">
      <h1 id="titulo-inicio" class="text-2xl font-extrabold text-slate-900">
        Compara precios en Armenia
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Encuentra donde comprar mas barato. Colabora reportando precios.
      </p>
    </div>

    <!-- Buscador principal -->
    <form
      class="relative mb-6"
      @submit.prevent="irABuscar"
      role="search"
      aria-label="Buscar productos"
    >
      <Search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
      <input
        v-model="busqueda"
        type="search"
        class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm transition-shadow placeholder:text-slate-400 focus:border-primary-500 focus:shadow-md focus:outline-none"
        placeholder="Buscar productos... ej: arroz, leche, huevos"
        aria-label="Nombre del producto"
      />
    </form>

    <!-- Categorias -->
    <section class="mb-6" aria-label="Categorias de productos">
      <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-none">
        <CategoriaChip
          v-for="cat in categorias"
          :key="cat.id"
          :categoria="cat"
          :activa="categoriaActiva === cat.id"
          @click="categoriaActiva = cat.id"
        />
      </div>
    </section>

    <!-- Productos con diferencias significativas -->
    <section aria-labelledby="productos-destacados">
      <div class="mb-3 flex items-center justify-between">
        <h2 id="productos-destacados" class="flex items-center gap-1.5 text-base font-bold text-slate-800">
          <TrendingDown class="h-4 w-4 text-success-500" aria-hidden="true" />
          Mejores precios hoy
        </h2>
        <router-link to="/buscar" class="flex items-center gap-0.5 text-xs font-semibold text-primary-600">
          Ver todos
          <ChevronRight class="h-3.5 w-3.5" aria-hidden="true" />
        </router-link>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <ProductoCard
          v-for="prod in productosDestacados"
          :key="prod.id"
          :producto="prod"
        />
      </div>
    </section>
  </section>
</template>
