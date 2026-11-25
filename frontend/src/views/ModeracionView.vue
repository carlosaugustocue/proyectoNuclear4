<script setup lang="ts">
/*
 * Consola de moderación (RF-20, RF-31). Se entra con una cuenta de rol MODERADOR (ADR-0025).
 * Las decisiones se registran en la bitácora del servidor (RNF-11); aquí nunca se ve quién reportó.
 */
import { computed, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { ShieldCheck, ShieldAlert, Store, Copy, Check, LogIn, RefreshCw } from 'lucide-vue-next'
import { isAxiosError } from 'axios'
import { useSesionStore } from '@/stores/sesion'
import { decidirCaso, listarCasos, listarPerfiles, verificarPerfil, type AccionModeracion, type CasoModeracion, type PerfilPendiente } from '@/api/moderacion'
import { mensajeDeError } from '@/api/client'
import { formatearPesos, tiempoRelativo } from '@/utils/formato'

const MIN_JUSTIFICACION = 10
const sesion = useSesionStore()
const { autenticado, esModerador, cuenta } = storeToRefs(sesion)
const pestana = ref<'casos' | 'perfiles'>('casos')
const casos = ref<CasoModeracion[]>([])
const perfiles = ref<PerfilPendiente[]>([])
const cargando = ref(false)
const aviso = ref<string | null>(null)
const errorAccion = ref<string | null>(null)
const justificaciones = reactive<Record<string, string>>({})
const enCurso = ref<string | null>(null)
const credencialEmitida = ref<{ perfilId: string; establecimiento: string; credencial: string } | null>(null)
const copiado = ref<string | null>(null)

const ETIQUETA_ORIGEN: Record<CasoModeracion['origen'], string> = {
  ANOMALIA_AUTOMATICA: 'Precio atípico (detección automática)',
  MARCA_USUARIO: 'Marcado por un usuario',
  CORRECCION_COMERCIO: 'Corrección pedida por un comercio',
}

const pestanas = computed(() => [
  { id: 'casos' as const, texto: 'Precios por revisar', total: casos.value.length, icono: ShieldAlert },
  { id: 'perfiles' as const, texto: 'Comercios por verificar', total: perfiles.value.length, icono: Store },
])

function esProhibido(e: unknown): boolean {
  return isAxiosError(e) && e.response?.status === 403
}

async function cargar() {
  cargando.value = true
  errorAccion.value = null
  try {
    const cab = sesion.cabeceras()
    const [c, p] = await Promise.all([listarCasos(cab), listarPerfiles(cab)])
    casos.value = c.items
    perfiles.value = p.items
  } catch (e) {
    errorAccion.value = esProhibido(e) ? 'Tu cuenta perdió el rol de moderador.' : mensajeDeError(e)
  } finally {
    cargando.value = false
  }
}

watch(esModerador, (si) => {
  if (si) void cargar()
  else {
    casos.value = []
    perfiles.value = []
    credencialEmitida.value = null
  }
}, { immediate: true })

function justificacionValida(id: string): boolean {
  return (justificaciones[id] ?? '').trim().length >= MIN_JUSTIFICACION
}

async function decidir(caso: CasoModeracion, accion: AccionModeracion, texto: string) {
  enCurso.value = caso.id
  errorAccion.value = null
  try {
    await decidirCaso(sesion.cabeceras(), caso.id, accion, (justificaciones[caso.id] ?? '').trim())
    casos.value = casos.value.filter((c) => c.id !== caso.id)
    aviso.value = `Caso resuelto: ${texto}. La decisión quedó en la bitácora.`
  } catch (e) {
    errorAccion.value = mensajeDeError(e)
  } finally {
    enCurso.value = null
  }
}

async function verificar(perfil: PerfilPendiente) {
  enCurso.value = perfil.perfilId
  errorAccion.value = null
  try {
    const r = await verificarPerfil(sesion.cabeceras(), perfil.perfilId)
    perfiles.value = perfiles.value.filter((p) => p.perfilId !== perfil.perfilId)
    credencialEmitida.value = { perfilId: r.perfilId, establecimiento: perfil.establecimiento?.nombre ?? '', credencial: r.credencial }
    aviso.value = null
  } catch (e) {
    errorAccion.value = mensajeDeError(e)
  } finally {
    enCurso.value = null
  }
}

async function copiar(texto: string, clave: string) {
  try {
    await navigator.clipboard.writeText(texto)
    copiado.value = clave
    setTimeout(() => { if (copiado.value === clave) copiado.value = null }, 2000)
  } catch {
    errorAccion.value = 'No se pudo copiar; selecciona el texto y cópialo a mano.'
  }
}
</script>

<template>
  <section aria-labelledby="titulo-moderacion">
    <div class="mb-4 flex items-start justify-between gap-3">
      <div>
        <h1 id="titulo-moderacion" class="flex items-center gap-2 text-lg font-bold text-slate-900">
          <ShieldCheck class="h-5 w-5 text-primary-600" aria-hidden="true" /> Consola de moderación
        </h1>
        <p class="text-xs text-slate-500">Revisa precios dudosos y verifica comercios. Cada decisión queda registrada.</p>
      </div>
      <p v-if="esModerador" class="shrink-0 rounded-full bg-primary-50 px-2 py-1 text-[11px] font-semibold text-primary-700">{{ cuenta?.seudonimo }}</p>
    </div>

    <!-- Acceso: cuenta con rol MODERADOR -->
    <div v-if="!esModerador" class="rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70" role="status">
      <p class="mb-3 text-sm text-slate-700">
        <template v-if="!autenticado">Entra con tu cuenta de moderador para revisar los casos.</template>
        <template v-else>Tu cuenta no tiene permisos de moderación. El equipo asigna este rol a cuentas verificadas.</template>
      </p>
      <router-link
        v-if="!autenticado"
        :to="{ path: '/cuenta', query: { volver: '/moderacion' } }"
        class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 active:bg-primary-800"
      >
        <LogIn class="h-4 w-4" aria-hidden="true" /> Entrar
      </router-link>
    </div>

    <template v-else>
      <div class="mb-3 flex items-center gap-2">
        <div class="flex flex-1 rounded-lg border border-slate-200 bg-white p-0.5" role="tablist" aria-label="Colas de moderación">
          <button
            v-for="p in pestanas"
            :id="`pestana-${p.id}`"
            :key="p.id"
            type="button"
            role="tab"
            :aria-selected="pestana === p.id"
            :aria-controls="`panel-${p.id}`"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-primary-500"
            :class="pestana === p.id ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
            @click="pestana = p.id"
          >
            <component :is="p.icono" class="h-3.5 w-3.5" aria-hidden="true" />
            {{ p.texto }}
            <span class="rounded-full px-1.5 text-[10px]" :class="pestana === p.id ? 'bg-white/25' : 'bg-slate-100'">{{ p.total }}</span>
          </button>
        </div>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white p-2 text-slate-600 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-primary-500"
          :aria-busy="cargando"
          aria-label="Actualizar"
          @click="cargar"
        >
          <RefreshCw class="h-4 w-4" :class="cargando && 'animate-spin'" aria-hidden="true" />
        </button>
      </div>

      <p v-if="aviso" class="mb-3 rounded-lg bg-success-50 p-3 text-xs text-success-800" role="status">{{ aviso }}</p>
      <p v-if="errorAccion" class="mb-3 rounded-lg bg-danger-50 p-3 text-xs text-danger-700" role="alert">{{ errorAccion }}</p>

      <!-- Credencial recién emitida: se muestra una sola vez -->
      <div v-if="credencialEmitida" class="mb-4 rounded-xl border-2 border-primary-300 bg-primary-50 p-4" role="status">
        <p class="mb-1 text-sm font-semibold text-primary-900">Comercio verificado: {{ credencialEmitida.establecimiento }}</p>
        <p class="mb-2 text-xs text-primary-800">Entrega esta credencial al comercio por un canal privado. <strong>No se vuelve a mostrar.</strong></p>
        <div class="flex items-center gap-2">
          <code class="flex-1 break-all rounded-lg bg-white px-2 py-1.5 text-xs text-slate-800">{{ credencialEmitida.credencial }}</code>
          <button type="button" class="rounded-lg bg-primary-600 p-2 text-white hover:bg-primary-700" aria-label="Copiar credencial" @click="copiar(credencialEmitida.credencial, 'credencial')">
            <component :is="copiado === 'credencial' ? Check : Copy" class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
        <button type="button" class="mt-2 text-xs font-semibold text-primary-700 hover:underline" @click="credencialEmitida = null">Ya la entregué</button>
      </div>

      <!-- Casos -->
      <div v-show="pestana === 'casos'" id="panel-casos" role="tabpanel" aria-labelledby="pestana-casos">
        <p v-if="!cargando && casos.length === 0" class="rounded-xl border border-dashed border-slate-300 py-8 text-center text-sm text-slate-500">
          No hay precios por revisar.
        </p>
        <ul class="space-y-3">
          <li v-for="caso in casos" :key="caso.id" class="rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70">
            <div class="mb-2 flex items-start justify-between gap-3">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-warning-700">{{ ETIQUETA_ORIGEN[caso.origen] }}</p>
                <template v-if="caso.observacion">
                  <p class="text-sm font-semibold text-slate-800">
                    {{ caso.observacion.producto }}<span v-if="caso.observacion.presentacion" class="font-normal text-slate-500"> · {{ caso.observacion.presentacion }}</span>
                  </p>
                  <p class="text-xs text-slate-500">{{ caso.observacion.establecimiento }} · {{ tiempoRelativo(caso.observacion.observadoEn) }}</p>
                </template>
                <p v-else class="text-sm text-slate-700">{{ caso.detalle }}</p>
              </div>
              <p v-if="caso.observacion" class="text-xl font-extrabold text-slate-900">{{ formatearPesos(caso.observacion.precio) }}</p>
            </div>
            <p v-if="caso.observacion && caso.detalle" class="mb-3 text-xs text-slate-600">{{ caso.detalle }}</p>

            <label :for="`just-${caso.id}`" class="mb-1 block text-xs font-semibold text-slate-700">Justificación (mínimo {{ MIN_JUSTIFICACION }} caracteres)</label>
            <textarea
              :id="`just-${caso.id}`"
              v-model="justificaciones[caso.id]"
              rows="2"
              maxlength="500"
              class="mb-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
            />
            <div class="flex flex-wrap gap-2">
              <template v-if="caso.observacion">
                <button
                  type="button"
                  class="rounded-lg bg-success-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-success-700 disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="!justificacionValida(caso.id) || enCurso === caso.id"
                  @click="decidir(caso, 'CONFIRMAR_OBSERVACION', 'precio confirmado')"
                >Confirmar precio</button>
                <button
                  type="button"
                  class="rounded-lg bg-danger-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-danger-700 disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="!justificacionValida(caso.id) || enCurso === caso.id"
                  @click="decidir(caso, 'INVALIDAR_OBSERVACION', 'precio invalidado y retirado de la comparación')"
                >Invalidar</button>
                <button
                  v-if="caso.observacion.autorConCuenta"
                  type="button"
                  class="rounded-lg border border-danger-600 px-3 py-1.5 text-xs font-semibold text-danger-700 hover:bg-danger-50 disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="!justificacionValida(caso.id) || enCurso === caso.id"
                  title="Suspende la cuenta del autor, cierra sus sesiones e invalida este precio"
                  @click="decidir(caso, 'SUSPENDER_CUENTA', 'cuenta del autor suspendida y precio invalidado')"
                >Suspender al autor</button>
              </template>
              <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!justificacionValida(caso.id) || enCurso === caso.id"
                @click="decidir(caso, 'CERRAR_SIN_CAMBIOS', 'cerrado sin cambios')"
              >Cerrar sin cambios</button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Perfiles -->
      <div v-show="pestana === 'perfiles'" id="panel-perfiles" role="tabpanel" aria-labelledby="pestana-perfiles">
        <p v-if="!cargando && perfiles.length === 0" class="rounded-xl border border-dashed border-slate-300 py-8 text-center text-sm text-slate-500">
          No hay comercios esperando verificación.
        </p>
        <ul class="space-y-3">
          <li v-for="perfil in perfiles" :key="perfil.perfilId" class="rounded-2xl bg-white p-4 shadow-tarjeta ring-1 ring-slate-200/70">
            <p class="text-sm font-semibold text-slate-800">{{ perfil.establecimiento?.nombre ?? 'Establecimiento desconocido' }}</p>
            <p class="text-xs text-slate-500">{{ perfil.establecimiento?.direccion }} · solicitado {{ tiempoRelativo(perfil.solicitadoEn) }}</p>
            <div class="my-2 flex items-center gap-2">
              <code class="flex-1 truncate rounded bg-slate-50 px-2 py-1 text-[11px] text-slate-600">{{ perfil.perfilId }}</code>
              <button type="button" class="rounded p-1 text-slate-500 hover:bg-slate-100" :aria-label="`Copiar el id del perfil de ${perfil.establecimiento?.nombre ?? ''}`" @click="copiar(perfil.perfilId, perfil.perfilId)">
                <component :is="copiado === perfil.perfilId ? Check : Copy" class="h-3.5 w-3.5" aria-hidden="true" />
              </button>
            </div>
            <p class="mb-2 text-[11px] text-slate-500">Antes de verificar, confirma por teléfono o en persona que quien lo pidió representa al establecimiento.</p>
            <button
              type="button"
              class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700 disabled:opacity-40"
              :disabled="enCurso === perfil.perfilId"
              @click="verificar(perfil)"
            >Verificar y emitir credencial</button>
          </li>
        </ul>
      </div>
    </template>
  </section>
</template>
