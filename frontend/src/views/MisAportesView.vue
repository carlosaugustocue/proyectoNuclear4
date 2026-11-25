<script setup lang="ts">
/* Mis aportes (RF-12, ADR-0026): mis reportes y su estado, y mi reputación (RN-07). Requiere sesión. */
import { ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { ArrowLeft, Award, LogIn } from 'lucide-vue-next'
import { useSesionStore } from '@/stores/sesion'
import { misAportes, type MisAportes } from '@/api/aportes'
import { mensajeDeError } from '@/api/client'
import BadgeConfianza from '@/components/BadgeConfianza.vue'
import { formatearPesos, tiempoRelativo } from '@/utils/formato'

const APORTES_PARA_REPUTACION = 5 // RN-07 ⚑
const { autenticado } = storeToRefs(useSesionStore())
const datos = ref<MisAportes | null>(null)
const pagina = ref(1)
const cargando = ref(false)
const error = ref<string | null>(null)

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    datos.value = await misAportes(pagina.value)
  } catch (e) {
    error.value = mensajeDeError(e)
  } finally {
    cargando.value = false
  }
}

watch([autenticado, pagina], ([si]) => { if (si) void cargar(); else datos.value = null }, { immediate: true })
</script>

<template>
  <section aria-labelledby="titulo-aportes">
    <div class="mb-4 flex items-center gap-2">
      <router-link to="/perfil" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Volver al perfil">
        <ArrowLeft class="h-5 w-5" />
      </router-link>
      <h1 id="titulo-aportes" class="text-lg font-bold text-slate-900">Mis aportes</h1>
    </div>

    <div v-if="!autenticado" class="rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70 text-sm text-slate-700" role="status">
      <p class="mb-3">Entra con tu cuenta para ver tus reportes y tu reputación.</p>
      <router-link :to="{ path: '/cuenta', query: { volver: '/mis-aportes' } }" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
        <LogIn class="h-4 w-4" aria-hidden="true" /> Entrar
      </router-link>
    </div>

    <template v-else>
      <p v-if="error" class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700" role="alert">{{ error }}</p>

      <!-- Reputación (RN-07): cuánto falta para que un aporte propio actualice el precio sin corroboración -->
      <section v-if="datos" class="mb-4 rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 p-4 text-white shadow-sm" aria-labelledby="titulo-reputacion">
        <h2 id="titulo-reputacion" class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-white/80">
          <Award class="h-4 w-4" aria-hidden="true" /> Tu reputación
        </h2>
        <p class="mt-1 text-3xl font-extrabold">{{ datos.reputacion.aportesConfirmados }}<span class="ml-1 text-sm font-semibold text-white/80">aportes corroborados</span></p>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/20" role="progressbar" :aria-valuenow="Math.min(datos.reputacion.aportesConfirmados, APORTES_PARA_REPUTACION)" aria-valuemin="0" :aria-valuemax="APORTES_PARA_REPUTACION" aria-label="Avance de reputación">
          <div class="h-full origin-left rounded-full bg-white transition-transform duration-500" :style="{ transform: `scaleX(${Math.min(datos.reputacion.aportesConfirmados / APORTES_PARA_REPUTACION, 1)})` }" />
        </div>
        <p class="mt-2 text-xs text-white/90">
          <template v-if="datos.reputacion.requiereCorroboracion">
            Tus reportes necesitan que otra persona los corrobore antes de reemplazar un precio vigente. Se gana con cuentas de al menos 7 días y {{ APORTES_PARA_REPUTACION }} aportes corroborados.
          </template>
          <template v-else>Tus reportes ya pueden actualizar el precio vigente por sí solos.</template>
        </p>
      </section>

      <p v-if="cargando && !datos" class="py-10 text-center text-sm text-slate-500" aria-live="polite">Cargando tus aportes…</p>
      <p v-else-if="datos && datos.items.length === 0" class="py-10 text-center text-sm text-slate-500">
        Todavía no has reportado precios con tu cuenta.
        <router-link to="/registrar" class="mt-2 block font-semibold text-primary-700 underline">Reporta el primero</router-link>
      </p>

      <ul v-else-if="datos" class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white" :aria-busy="cargando">
        <li v-for="a in datos.items" :key="a.observacionId" class="flex items-start justify-between gap-3 p-3">
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-800">{{ a.producto ?? 'Producto' }} <span class="font-normal text-slate-500">· {{ a.presentacion }}</span></p>
            <p class="truncate text-xs text-slate-500">{{ a.establecimiento }} · {{ tiempoRelativo(a.observadoEn) }}</p>
          </div>
          <div class="shrink-0 text-right">
            <p class="text-sm font-bold text-slate-900">{{ formatearPesos(a.precio) }}</p>
            <BadgeConfianza v-if="a.estadoConfianza !== 'PENDIENTE_EVALUACION'" :estado="a.estadoConfianza" />
            <span v-else class="text-[10px] font-semibold uppercase text-slate-500">En evaluación</span>
          </div>
        </li>
      </ul>

      <nav v-if="datos && datos.pagina.total > datos.pagina.tamano" class="mt-3 flex items-center justify-between text-xs" aria-label="Páginas">
        <button type="button" class="rounded-lg px-3 py-1.5 font-semibold text-primary-700 hover:bg-primary-50 disabled:text-slate-300" :disabled="pagina === 1" @click="pagina--">Anteriores</button>
        <span class="text-slate-500">Página {{ pagina }} de {{ Math.ceil(datos.pagina.total / datos.pagina.tamano) }}</span>
        <button type="button" class="rounded-lg px-3 py-1.5 font-semibold text-primary-700 hover:bg-primary-50 disabled:text-slate-300" :disabled="pagina * datos.pagina.tamano >= datos.pagina.total" @click="pagina++">Siguientes</button>
      </nav>
    </template>
  </section>
</template>
