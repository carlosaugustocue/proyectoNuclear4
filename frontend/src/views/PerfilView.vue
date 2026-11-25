<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { User, Shield, CloudOff, RefreshCw, Trash2, Store, ChevronRight, ListChecks } from 'lucide-vue-next'
import { useReportesStore } from '@/stores/reportes'
import { useSesionStore } from '@/stores/sesion'
import { formatearPesos, tiempoRelativo } from '@/utils/formato'

const reportes = useReportesStore()
const { pendientes, sincronizando, enLinea } = storeToRefs(reportes)
const { autenticado, cuenta } = storeToRefs(useSesionStore())
const enEspera = computed(() => pendientes.value.filter((r) => r.estado === 'pendiente').length)
</script>

<template>
  <section aria-labelledby="titulo-perfil">
    <h1 id="titulo-perfil" class="mb-1 text-lg font-bold text-slate-900">Tu actividad</h1>
    <p class="mb-6 text-xs text-slate-500">Puedes consultar y reportar precios sin crear una cuenta.</p>

    <!-- Cola offline (EC-09) -->
    <section class="mb-6 rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70" aria-labelledby="titulo-cola">
      <div class="mb-2 flex items-center justify-between">
        <h2 id="titulo-cola" class="flex items-center gap-2 text-sm font-semibold text-slate-800">
          <CloudOff class="h-4 w-4 text-primary-600" aria-hidden="true" />
          Reportes por enviar
        </h2>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-50 disabled:text-slate-400"
          :disabled="sincronizando || !enLinea || enEspera === 0"
          @click="reportes.sincronizar()"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': sincronizando }" aria-hidden="true" />
          Enviar ahora
        </button>
      </div>
      <p class="text-xs text-slate-500" aria-live="polite">
        <template v-if="pendientes.length === 0">No tienes reportes pendientes.</template>
        <template v-else>{{ enEspera }} en espera{{ enLinea ? '' : ' · sin conexión: se enviarán solos al volver la red' }}.</template>
      </p>
      <ul v-if="pendientes.length > 0" class="mt-3 divide-y divide-slate-100">
        <li v-for="r in pendientes" :key="r.claveIdempotencia" class="flex items-center justify-between gap-2 py-2 text-xs">
          <div>
            <p class="font-semibold text-slate-700">{{ r.etiqueta.producto }} · {{ formatearPesos(r.cuerpo.precio) }}</p>
            <p class="text-slate-500">{{ r.etiqueta.establecimiento }} · {{ tiempoRelativo(new Date(r.creadoEn).toISOString()) }}</p>
            <p v-if="r.estado === 'rechazado'" class="text-danger-700">El servidor rechazó este reporte.</p>
          </div>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-500 hover:bg-danger-50 hover:text-danger-700"
            :aria-label="`Descartar reporte de ${r.etiqueta.producto}`"
            @click="reportes.descartar(r.claveIdempotencia)"
          >
            <Trash2 class="h-4 w-4" aria-hidden="true" />
          </button>
        </li>
      </ul>
    </section>

    <router-link to="/cuenta" class="mb-6 flex items-center gap-3 rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70 transition hover:ring-primary-300 hover:bg-primary-50/40">
      <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full" :class="autenticado ? 'bg-primary-100' : 'bg-slate-100'">
        <User class="h-6 w-6" :class="autenticado ? 'text-primary-700' : 'text-slate-500'" aria-hidden="true" />
      </div>
      <span class="flex-1">
        <template v-if="autenticado">
          <span class="block text-sm font-semibold text-slate-800">{{ cuenta?.seudonimo }}</span>
          <span class="block text-xs text-slate-500">Tus reportes suman reputación. Gestiona tus datos.</span>
        </template>
        <template v-else>
          <span class="block text-sm font-semibold text-slate-800">Entra o crea tu cuenta</span>
          <span class="block text-xs text-slate-500">Opcional: sin cuenta tus aportes van con un seudónimo anónimo.</span>
        </template>
      </span>
      <ChevronRight class="h-4 w-4 text-slate-400" aria-hidden="true" />
    </router-link>

    <router-link v-if="autenticado" to="/mis-aportes" class="mb-6 -mt-3 flex items-center gap-3 rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70 transition hover:ring-primary-300 hover:bg-primary-50/40">
      <ListChecks class="h-5 w-5 text-primary-600" aria-hidden="true" />
      <span class="flex-1">
        <span class="block text-sm font-semibold text-slate-800">Mis aportes</span>
        <span class="block text-xs text-slate-500">Tus reportes, su estado y tu reputación.</span>
      </span>
      <ChevronRight class="h-4 w-4 text-slate-400" aria-hidden="true" />
    </router-link>

    <router-link to="/comercio" class="mb-6 flex items-center gap-3 rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70 transition hover:ring-primary-300 hover:bg-primary-50/40">
      <Store class="h-5 w-5 text-primary-600" aria-hidden="true" />
      <span class="flex-1">
        <span class="block text-sm font-semibold text-slate-800">¿Tienes un comercio?</span>
        <span class="block text-xs text-slate-500">Publica tu precio oficial y tus promociones. Nunca cambian el orden de la comparación.</span>
      </span>
      <ChevronRight class="h-4 w-4 text-slate-400" aria-hidden="true" />
    </router-link>

    <router-link to="/moderacion" class="mb-6 -mt-4 flex items-center gap-2 px-1 text-xs font-semibold text-slate-500 hover:text-primary-700">
      <Shield class="h-3.5 w-3.5" aria-hidden="true" /> Soy moderador
    </router-link>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
      <div class="mb-2 flex items-center gap-2">
        <Shield class="h-4 w-4 text-primary-600" aria-hidden="true" />
        <p class="text-xs font-semibold text-slate-700">Privacidad</p>
      </div>
      <p class="text-xs leading-relaxed text-slate-500">
        Tu identidad nunca se asocia públicamente a los precios que reportas. No almacenamos la ubicación de tu
        dispositivo: solo se usa en el momento, si la pides, para ordenar por distancia.
        <router-link to="/privacidad" class="font-semibold text-primary-700 underline">Política de tratamiento de datos</router-link> (Ley 1581 de 2012).
      </p>
    </div>
  </section>
</template>
