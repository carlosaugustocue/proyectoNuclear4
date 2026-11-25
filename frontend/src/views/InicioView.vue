<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Search, TrendingDown, ChevronRight } from 'lucide-vue-next'
import ProductoCard from '@/components/ProductoCard.vue'
import CategoriaChip from '@/components/CategoriaChip.vue'
import { useProductosConPrecio } from '@/composables/useProductosConPrecio'
import { CATEGORIAS } from '@/utils/catalogo'

const router = useRouter()
const busqueda = ref('')
const { productos, resumenes, cargando, error, cargar } = useProductosConPrecio()

onMounted(() => cargar({ tamano: 6 }))

function irABuscar(categoria?: string | null) {
  const query: Record<string, string> = {}
  if (busqueda.value.trim()) query.q = busqueda.value.trim()
  if (categoria) query.categoria = categoria
  router.push({ name: 'buscar', query })
}
</script>

<template>
  <section aria-labelledby="titulo-inicio">
    <div class="mb-6 text-center">
      <h1 id="titulo-inicio" class="text-2xl font-extrabold text-slate-900">Compara precios en Armenia</h1>
      <p class="mt-1 text-sm text-slate-500">Encuentra dónde comprar más barato. Colabora reportando precios.</p>
    </div>

    <form class="relative mb-6" role="search" aria-label="Buscar productos" @submit.prevent="irABuscar()">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
      <input
        v-model="busqueda"
        type="search"
        class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm transition-shadow placeholder:text-slate-500 focus:border-primary-500 focus:shadow-md focus:outline-none"
        placeholder="Buscar productos... ej: arroz, leche, huevos"
        aria-label="Nombre del producto"
      />
    </form>

    <nav class="mb-6" aria-label="Categorías de productos">
      <div class="flex gap-2 overflow-x-auto pb-2">
        <CategoriaChip
          v-for="cat in CATEGORIAS"
          :key="cat.nombre"
          :nombre="cat.nombre"
          :activa="cat.valor === null"
          @click="irABuscar(cat.valor)"
        />
      </div>
    </nav>

    <section aria-labelledby="productos-destacados">
      <div class="mb-3 flex items-center justify-between">
        <h2 id="productos-destacados" class="flex items-center gap-1.5 text-base font-bold text-slate-800">
          <TrendingDown class="h-4 w-4 text-success-500" aria-hidden="true" />
          Productos del catálogo
        </h2>
        <router-link to="/buscar" class="flex items-center gap-0.5 text-xs font-semibold text-primary-600">
          Ver todos <ChevronRight class="h-3.5 w-3.5" aria-hidden="true" />
        </router-link>
      </div>

      <p v-if="error" class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>
      <p v-else-if="cargando && productos.length === 0" class="py-8 text-center text-sm text-slate-500" aria-live="polite">Cargando precios…</p>
      <div v-else class="grid grid-cols-2 gap-3">
        <ProductoCard v-for="prod in productos" :key="prod.id" :producto="prod" :resumen="resumenes[prod.id]" />
      </div>
    </section>
  </section>
</template>
