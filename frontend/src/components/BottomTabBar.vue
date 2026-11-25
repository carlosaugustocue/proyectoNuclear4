<script setup lang="ts">
import { useRoute } from 'vue-router'
import { Home, Search, Plus, ShoppingBasket, Store, User } from 'lucide-vue-next'

const route = useRoute()

/* «Registrar» va al centro y elevado: es la acción principal del reportante (EC-01). */
const tabs = [
  { to: '/', label: 'Inicio', icon: Home },
  { to: '/buscar', label: 'Buscar', icon: Search },
  { to: '/canasta', label: 'Canasta', icon: ShoppingBasket },
  { to: '/registrar', label: 'Registrar', icon: Plus, principal: true },
  { to: '/establecimientos', label: 'Tiendas', icon: Store },
  { to: '/perfil', label: 'Perfil', icon: User },
]

const activa = (to: string) => (to === '/' ? route.path === '/' : route.path.startsWith(to))
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200/80 bg-white/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-md"
    aria-label="Navegación principal"
  >
    <ul class="mx-auto grid h-16 max-w-2xl grid-cols-6 items-center px-1">
      <li v-for="tab in tabs" :key="tab.to" class="flex justify-center">
        <router-link
          v-if="tab.principal"
          :to="tab.to"
          class="-mt-7 flex flex-col items-center gap-1 rounded-2xl text-[11px] font-semibold text-slate-800"
          :aria-label="tab.label"
          :aria-current="activa(tab.to) ? 'page' : undefined"
        >
          <span
            class="grid h-14 w-14 place-items-center rounded-2xl bg-accent-400 text-slate-900 shadow-flotante ring-4 ring-surface transition-transform duration-200 ease-out hover:-translate-y-0.5 active:scale-95"
            :class="activa(tab.to) ? 'ring-accent-100' : ''"
          >
            <component :is="tab.icon" class="h-7 w-7 transition-transform duration-200" stroke-width="2.5" aria-hidden="true" />
          </span>
          <span>{{ tab.label }}</span>
        </router-link>
        <router-link
          v-else
          :to="tab.to"
          class="group flex min-w-14 flex-col items-center gap-0.5 rounded-xl px-1 py-1 text-[11px] transition-colors"
          :class="activa(tab.to) ? 'font-semibold text-primary-700' : 'font-medium text-slate-500 hover:text-slate-800'"
          :aria-label="tab.label"
          :aria-current="activa(tab.to) ? 'page' : undefined"
        >
          <span
            class="grid h-7 w-12 place-items-center rounded-full transition-colors duration-200"
            :class="activa(tab.to) ? 'bg-primary-100' : 'group-hover:bg-slate-100'"
          >
            <component :is="tab.icon" class="h-5 w-5" :stroke-width="activa(tab.to) ? 2.4 : 2" aria-hidden="true" />
          </span>
          <span>{{ tab.label }}</span>
        </router-link>
      </li>
    </ul>
  </nav>
</template>
