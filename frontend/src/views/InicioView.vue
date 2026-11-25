<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Search, ChevronRight, ArrowRight, Users, ShieldCheck, Scale, LayoutGrid } from 'lucide-vue-next'
import ProductoCard from '@/components/ProductoCard.vue'
import { useProductosConPrecio } from '@/composables/useProductosConPrecio'
import { CATEGORIAS } from '@/utils/catalogo'
import { ilustracionDe } from '@/utils/imagenProducto'
import { ICONOS_CATEGORIA } from '@/utils/iconosCategoria'

const router = useRouter()
const busqueda = ref('')
const { productos, resumenes, total, cargando, error, cargar } = useProductosConPrecio()

onMounted(() => cargar({ tamano: 50 }))

/** Un producto con precio por categoría (hasta 6): la portada muestra variedad, no las primeras letras del alfabeto. */
const destacados = computed(() => {
  const vistas = new Set<string>()
  return productos.value
    .filter((p) => resumenes.value[p.id]?.precioPorUnidadMin && !vistas.has(p.categoria) && vistas.add(p.categoria))
    .slice(0, 6)
})

const categorias = CATEGORIAS.filter((c) => c.valor !== null)

function irABuscar(categoria?: string | null) {
  const query: Record<string, string> = {}
  if (busqueda.value.trim()) query.q = busqueda.value.trim()
  if (categoria) query.categoria = categoria
  router.push({ name: 'buscar', query })
}

const PASOS = [
  { icono: Users, titulo: 'La comunidad reporta', texto: 'Precios tomados en la góndola, con fecha.' },
  { icono: ShieldCheck, titulo: 'Se corroboran', texto: 'Dos personas distintas confirman el mismo precio.' },
  { icono: Scale, titulo: 'Comparas con criterio', texto: 'Por precio por kilo o litro. Nadie paga por salir primero.' },
]
</script>

<template>
  <section aria-labelledby="titulo-inicio" class="space-y-8">
    <!-- Hero: el buscador es el protagonista -->
    <div class="grano relative overflow-hidden rounded-[1.75rem] bg-primary-950 px-5 pb-6 pt-7 text-white shadow-elevada">
      <svg class="pointer-events-none absolute -right-10 -top-8 h-48 w-48 text-primary-800" viewBox="0 0 200 200" aria-hidden="true">
        <g fill="currentColor">
          <path d="M60 30h52l58 58-52 52-58-58Z" opacity="0.55" transform="rotate(12 100 100)" />
          <circle cx="82" cy="58" r="8" class="text-accent-400" fill="currentColor" />
          <path d="M20 120h40l44 44-40 40-44-44Z" opacity="0.35" transform="rotate(-18 60 160)" />
        </g>
      </svg>
      <p class="relative inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold text-primary-100 ring-1 ring-white/15">
        <span class="h-1.5 w-1.5 rounded-full bg-accent-400" aria-hidden="true" />
        Precios de la comunidad · Armenia
      </p>
      <h1 id="titulo-inicio" class="relative mt-3 max-w-[16ch] text-[2rem] font-extrabold leading-[1.05] tracking-tight sm:text-4xl">
        ¿Dónde sale más <span class="text-accent-300">barato</span> hoy?
      </h1>
      <p class="relative mt-2 max-w-[34ch] text-sm text-primary-100">
        Compara tiendas de barrio, minimercados y supermercados por precio por kilo o litro.
      </p>

      <form class="relative mt-5" role="search" aria-label="Buscar productos" @submit.prevent="irABuscar()">
        <Search class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" aria-hidden="true" />
        <input
          v-model="busqueda"
          type="search"
          class="h-14 w-full rounded-2xl border-0 bg-white pl-12 pr-28 text-base text-slate-900 shadow-lg ring-1 ring-black/5 placeholder:text-slate-500 focus:outline-none focus:ring-4 focus:ring-accent-300/60"
          placeholder="Arroz, leche, huevos…"
          aria-label="Nombre del producto"
        />
        <button
          type="submit"
          class="absolute right-2 top-1/2 inline-flex h-10 -translate-y-1/2 items-center gap-1 rounded-xl bg-accent-400 px-4 text-sm font-bold text-slate-900 transition-transform hover:bg-accent-300 active:scale-95"
        >
          Buscar
        </button>
      </form>

      <p v-if="total" class="relative mt-4 text-xs text-primary-200">
        <span class="cifra text-sm font-bold text-white">{{ total }}</span> productos en el catálogo, con la referencia mayorista del DANE
      </p>
    </div>

    <!-- Categorías -->
    <nav aria-label="Categorías de productos">
      <h2 class="sr-only">Categorías</h2>
      <ul class="sin-barra -mx-4 flex gap-3 overflow-x-auto px-4 pb-1">
        <li>
          <button type="button" class="group flex w-[4.5rem] flex-col items-center gap-1.5 rounded-2xl text-xs font-semibold text-slate-700" @click="irABuscar()">
            <span class="grid h-16 w-16 place-items-center rounded-2xl bg-slate-900 text-white shadow-tarjeta transition-transform duration-200 group-hover:-translate-y-0.5 group-active:scale-95">
              <LayoutGrid class="h-6 w-6" aria-hidden="true" />
            </span>
            Todos
          </button>
        </li>
        <li v-for="cat in categorias" :key="cat.nombre">
          <button type="button" class="group flex w-[4.5rem] flex-col items-center gap-1.5 rounded-2xl text-center text-xs font-semibold leading-tight text-slate-700" @click="irABuscar(cat.valor)">
            <span
              class="grid h-16 w-16 place-items-center rounded-2xl shadow-tarjeta ring-1 ring-black/5 transition-transform duration-200 group-hover:-translate-y-0.5 group-active:scale-95"
              :class="ilustracionDe(cat.nombre).clases"
            >
              <component :is="ICONOS_CATEGORIA[ilustracionDe(cat.nombre).icono]" class="h-7 w-7" aria-hidden="true" />
            </span>
            {{ cat.nombre }}
          </button>
        </li>
      </ul>
    </nav>

    <!-- Productos -->
    <section aria-labelledby="productos-destacados">
      <div class="mb-3 flex items-end justify-between">
        <div>
          <h2 id="productos-destacados" class="text-xl font-bold text-slate-900">Precios de hoy</h2>
          <p class="text-xs text-slate-500">El menor precio vigente por unidad en cada producto</p>
        </div>
        <router-link to="/buscar" class="flex shrink-0 items-center gap-0.5 whitespace-nowrap rounded-lg text-sm font-semibold text-primary-700 hover:text-primary-800">
          Ver todos <ChevronRight class="h-4 w-4" aria-hidden="true" />
        </router-link>
      </div>

      <p v-if="error" class="rounded-xl bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>
      <div v-else-if="cargando && productos.length === 0" class="grid grid-cols-2 gap-3" aria-live="polite" aria-busy="true">
        <span class="sr-only">Cargando precios…</span>
        <div v-for="n in 4" :key="n" class="h-56 animate-pulse rounded-2xl bg-white/70 ring-1 ring-slate-200" />
      </div>
      <ul v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <li v-for="(prod, i) in destacados" :key="prod.id" class="aparecer" :style="{ animationDelay: `${i * 40}ms` }">
          <ProductoCard :producto="prod" :resumen="resumenes[prod.id]" />
        </li>
      </ul>
    </section>

    <!-- Cómo se gana la confianza (neutralidad y credibilidad, guía §12) -->
    <section aria-labelledby="titulo-confianza" class="rounded-3xl bg-white p-5 shadow-tarjeta ring-1 ring-slate-200/70">
      <h2 id="titulo-confianza" class="text-lg font-bold text-slate-900">Precios en los que puedes confiar</h2>
      <ol class="mt-4 space-y-4">
        <li v-for="(paso, i) in PASOS" :key="paso.titulo" class="flex gap-3">
          <span class="relative grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary-700">
            <component :is="paso.icono" class="h-5 w-5" aria-hidden="true" />
            <span class="cifra absolute -right-1.5 -top-1.5 grid h-5 w-5 place-items-center rounded-full bg-slate-900 text-[10px] font-bold text-white">{{ i + 1 }}</span>
          </span>
          <div>
            <p class="text-sm font-semibold text-slate-900">{{ paso.titulo }}</p>
            <p class="text-sm text-slate-600">{{ paso.texto }}</p>
          </div>
        </li>
      </ol>
    </section>

    <!-- Invitación a reportar (EC-01) -->
    <router-link
      to="/registrar"
      class="group flex items-center gap-4 rounded-3xl bg-accent-400 p-5 text-slate-900 shadow-tarjeta transition-transform duration-200 hover:-translate-y-0.5"
    >
      <div class="flex-1">
        <p class="font-display text-lg font-bold leading-tight">¿Estás en una tienda?</p>
        <p class="text-sm text-slate-800">Registra un precio en menos de 15 segundos, incluso sin señal.</p>
      </div>
      <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-slate-900 text-white transition-transform duration-200 group-hover:translate-x-1">
        <ArrowRight class="h-5 w-5" aria-hidden="true" />
      </span>
    </router-link>
  </section>
</template>
