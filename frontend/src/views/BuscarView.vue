<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { Search } from 'lucide-vue-next'
import ProductoCard from '@/components/ProductoCard.vue'
import CategoriaChip from '@/components/CategoriaChip.vue'
import { useProductosConPrecio } from '@/composables/useProductosConPrecio'
import { CATEGORIAS } from '@/utils/catalogo'

const RETARDO_BUSQUEDA_MS = 300

const route = useRoute()
const router = useRouter()
const busqueda = ref(typeof route.query.q === 'string' ? route.query.q : '')
const categoria = ref<string | null>(typeof route.query.categoria === 'string' ? route.query.categoria : null)
const { productos, resumenes, total, cargando, error, cargar } = useProductosConPrecio()
const inputRef = ref<HTMLInputElement>()
let temporizador: ReturnType<typeof setTimeout> | undefined

function consultar() {
  const q = busqueda.value.trim()
  // La búsqueda queda en la URL para poder compartirla o volver atrás
  router.replace({ query: { ...(q ? { q } : {}), ...(categoria.value ? { categoria: categoria.value } : {}) } })
  void cargar({ q: q || undefined, categoria: categoria.value ?? undefined, tamano: 40 })
}

watch(busqueda, () => {
  clearTimeout(temporizador)
  temporizador = setTimeout(consultar, RETARDO_BUSQUEDA_MS)
})
watch(categoria, consultar)

/*
 * Si se elige un resultado antes de que venza el retardo, la búsqueda pendiente no debe ejecutarse:
 * su router.replace cancelaría la navegación en curso (la vista de destino se carga en diferido).
 */
onBeforeRouteLeave(() => clearTimeout(temporizador))
onBeforeUnmount(() => clearTimeout(temporizador))

onMounted(() => {
  inputRef.value?.focus()
  consultar()
})
</script>

<template>
  <section aria-labelledby="titulo-buscar">
    <h1 id="titulo-buscar" class="sr-only">Buscar productos</h1>

    <div class="relative mb-4">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
      <input
        ref="inputRef"
        v-model="busqueda"
        type="search"
        class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm placeholder:text-slate-500 focus:border-primary-500 focus:outline-none"
        placeholder="Buscar por nombre, marca o alias…"
        aria-label="Buscar productos"
      />
    </div>

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Filtrar por categoría">
      <CategoriaChip
        v-for="cat in CATEGORIAS"
        :key="cat.nombre"
        :nombre="cat.nombre"
        :activa="categoria === cat.valor"
        @click="categoria = cat.valor"
      />
    </div>

    <p class="mb-3 text-xs font-medium text-slate-500" aria-live="polite">
      {{ cargando ? 'Buscando…' : `${total} producto${total !== 1 ? 's' : ''} encontrado${total !== 1 ? 's' : ''}` }}
    </p>

    <p v-if="error" class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>
    <div v-else-if="productos.length > 0" class="grid grid-cols-2 gap-3">
      <ProductoCard v-for="prod in productos" :key="prod.id" :producto="prod" :resumen="resumenes[prod.id]" />
    </div>
    <div v-else-if="!cargando" class="py-12 text-center">
      <p class="text-sm text-slate-500">No se encontraron productos</p>
      <p class="mt-1 text-xs text-slate-500">Intenta con otro nombre o categoría</p>
    </div>
  </section>
</template>
