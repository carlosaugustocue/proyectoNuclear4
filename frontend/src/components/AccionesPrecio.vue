<script setup lang="ts">
/*
 * Acciones de la comunidad sobre un precio (ADR-0026): «Sigue igual» (RF-10) y «Marcar como dudoso»
 * (RF-19). Ninguna cambia la posición: la confirmación pasa por las reglas de confianza y la marca la
 * revisa un moderador. Sin sesión, se invita a entrar.
 */
import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { CheckCheck, Flag, LogIn } from 'lucide-vue-next'
import { isAxiosError } from 'axios'
import { useSesionStore } from '@/stores/sesion'
import { confirmarPrecio, marcarPrecio, nuevaClaveConfirmacion, MOTIVOS_MARCA, type MotivoMarca } from '@/api/aportes'
import { mensajeDeError } from '@/api/client'

const props = defineProps<{ observacionId: string; establecimiento: string }>()
const emit = defineEmits<{ confirmado: [] }>()

const { autenticado } = storeToRefs(useSesionStore())
const route = useRoute()

const estado = ref<'inicial' | 'pidiendo-sesion' | 'marcando' | 'enviando'>('inicial')
const aviso = ref<{ tipo: 'ok' | 'error'; texto: string } | null>(null)
const motivo = ref<MotivoMarca>('PRECIO_INCORRECTO')
const comentario = ref('')
let claveConfirmacion: string | null = null // se conserva si la red falla, para reintentar sin duplicar

function requiereSesion(): boolean {
  if (autenticado.value) return false
  estado.value = 'pidiendo-sesion'
  return true
}

async function confirmar() {
  if (requiereSesion()) return
  estado.value = 'enviando'
  aviso.value = null
  claveConfirmacion ??= nuevaClaveConfirmacion()
  try {
    await confirmarPrecio(props.observacionId, claveConfirmacion)
    claveConfirmacion = null
    aviso.value = { tipo: 'ok', texto: 'Gracias: tu confirmación ayuda a corroborar este precio.' }
    emit('confirmado')
  } catch (e) {
    if (isAxiosError(e) && e.response) claveConfirmacion = null // el servidor ya respondió: no hay nada que reintentar
    aviso.value = { tipo: 'error', texto: mensajeDeError(e) }
  } finally {
    estado.value = 'inicial'
  }
}

function abrirMarca() {
  if (requiereSesion()) return
  aviso.value = null
  estado.value = 'marcando'
}

async function enviarMarca() {
  estado.value = 'enviando'
  try {
    await marcarPrecio(props.observacionId, motivo.value, comentario.value)
    comentario.value = ''
    aviso.value = { tipo: 'ok', texto: 'Gracias. Un moderador revisará este precio; mientras tanto no cambia nada.' }
    estado.value = 'inicial'
  } catch (e) {
    aviso.value = { tipo: 'error', texto: mensajeDeError(e) }
    estado.value = 'marcando'
  }
}
</script>

<template>
  <div class="mt-3 border-t border-slate-100 pt-2.5">
    <div v-if="estado !== 'marcando'" class="flex flex-wrap items-center gap-2">
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-full border border-success-600/40 px-2.5 py-1 text-xs font-semibold text-success-700 transition-colors hover:bg-success-50 disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-success-600"
        :disabled="estado === 'enviando'"
        @click="confirmar"
      >
        <CheckCheck class="h-3.5 w-3.5" aria-hidden="true" /> Sigue igual<span class="sr-only"> en {{ establecimiento }}</span>
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-primary-500"
        :disabled="estado === 'enviando'"
        @click="abrirMarca"
      >
        <Flag class="h-3.5 w-3.5" aria-hidden="true" /> Marcar como dudoso<span class="sr-only"> en {{ establecimiento }}</span>
      </button>
    </div>

    <p v-if="estado === 'pidiendo-sesion'" class="mt-2 flex flex-wrap items-center gap-1 text-xs text-slate-600" role="status">
      Para confirmar o marcar precios necesitas una cuenta.
      <router-link :to="{ path: '/cuenta', query: { volver: route.fullPath } }" class="inline-flex items-center gap-1 font-semibold text-primary-700 underline">
        <LogIn class="h-3 w-3" aria-hidden="true" /> Entrar
      </router-link>
    </p>

    <form v-if="estado === 'marcando'" class="space-y-2" @submit.prevent="enviarMarca">
      <fieldset>
        <legend class="mb-1 text-xs font-semibold text-slate-800">¿Qué tiene de raro este precio?</legend>
        <div class="flex flex-wrap gap-1.5">
          <label
            v-for="m in MOTIVOS_MARCA"
            :key="m.valor"
            class="cursor-pointer rounded-full border px-2.5 py-1 text-xs transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-primary-500"
            :class="motivo === m.valor ? 'border-primary-600 bg-primary-50 font-semibold text-primary-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
          >
            <input v-model="motivo" type="radio" name="motivo" :value="m.valor" class="sr-only" /> {{ m.texto }}
          </label>
        </div>
      </fieldset>
      <label class="block text-xs text-slate-600">
        Comentario (opcional, lo lee solo el moderador)
        <textarea v-model="comentario" rows="2" maxlength="280" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100" />
      </label>
      <div class="flex gap-2">
        <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-900">Enviar</button>
        <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100" @click="estado = 'inicial'">Cancelar</button>
      </div>
    </form>

    <p
      v-if="aviso"
      class="mt-2 rounded-lg p-2 text-xs"
      :class="aviso.tipo === 'ok' ? 'bg-success-50 text-success-800' : 'bg-danger-50 text-danger-700'"
      :role="aviso.tipo === 'ok' ? 'status' : 'alert'"
    >{{ aviso.texto }}</p>
  </div>
</template>
