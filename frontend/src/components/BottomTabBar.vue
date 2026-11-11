<script setup lang="ts">
import { useRoute } from 'vue-router'
import { Home, Search, PlusCircle, Store, User } from 'lucide-vue-next'

const route = useRoute()

const tabs = [
  { to: '/', label: 'Inicio', icon: Home },
  { to: '/buscar', label: 'Buscar', icon: Search },
  { to: '/registrar', label: 'Registrar', icon: PlusCircle },
  { to: '/establecimientos', label: 'Tiendas', icon: Store },
  { to: '/perfil', label: 'Perfil', icon: User },
]
</script>

<template>
  <nav
    class="fixed bottom-0 left-0 right-0 z-50 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)]"
    role="navigation"
    aria-label="Navegacion principal"
  >
    <div class="mx-auto flex max-w-2xl items-center justify-around">
      <router-link
        v-for="tab in tabs"
        :key="tab.to"
        :to="tab.to"
        class="flex flex-col items-center gap-0.5 px-3 py-2 text-xs transition-colors"
        :class="route.path === tab.to ? 'text-primary-600 font-semibold' : 'text-slate-400'"
        :aria-label="tab.label"
        :aria-current="route.path === tab.to ? 'page' : undefined"
      >
        <component :is="tab.icon" class="h-5 w-5" aria-hidden="true" />
        <span>{{ tab.label }}</span>
      </router-link>
    </div>
  </nav>
</template>
