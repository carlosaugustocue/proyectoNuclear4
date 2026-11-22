<script setup lang="ts">
/*
 * Cuenta (RF-22 a RF-24, ADR-0025). Crear cuenta exige la autorización expresa de tratamiento de
 * datos (Ley 1581). La sesión vive solo en memoria: al recargar hay que volver a entrar.
 */
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { UserRound, LogIn, UserPlus, LogOut, KeyRound, Trash2, ShieldCheck, Eye, EyeOff } from 'lucide-vue-next'
import { useSesionStore } from '@/stores/sesion'
import { cambiarContrasena, miCuenta, suprimirCuenta, type MiCuenta } from '@/api/identidad'
import { mensajeDeError, todosLosErrores } from '@/api/client'

const MINIMO_CONTRASENA = 10
const sesion = useSesionStore()
const { autenticado, cuenta } = storeToRefs(sesion)
const route = useRoute()
const router = useRouter()

const modo = ref<'entrar' | 'crear'>(route.query.modo === 'crear' ? 'crear' : 'entrar')
const formulario = reactive({ correo: '', contrasena: '', acepta: false })
const verContrasena = ref(false)
const errores = ref<string[]>([])
const enviando = ref(false)

const datos = ref<MiCuenta | null>(null)
const cambio = reactive({ actual: '', nueva: '' })
const avisoCambio = ref<string | null>(null)
const erroresCambio = ref<string[]>([])
const confirmandoBorrado = ref(false)

/** Solo rutas internas: evita redirecciones abiertas con ?volver=https://… */
const destino = computed(() => {
  const v = route.query.volver
  return typeof v === 'string' && v.startsWith('/') && !v.startsWith('//') ? v : null
})

const puedeEnviar = computed(() =>
  formulario.correo.trim() !== '' && formulario.contrasena.length >= (modo.value === 'crear' ? MINIMO_CONTRASENA : 1)
  && (modo.value === 'entrar' || formulario.acepta))

async function enviar() {
  errores.value = []
  enviando.value = true
  try {
    if (modo.value === 'crear') await sesion.registrar(formulario.correo, formulario.contrasena, formulario.acepta)
    else await sesion.iniciar(formulario.correo, formulario.contrasena)
    formulario.contrasena = ''
    if (destino.value) await router.push(destino.value)
  } catch (e) {
    errores.value = todosLosErrores(e)
  } finally {
    enviando.value = false
  }
}

async function cargarDatos() {
  try {
    datos.value = await miCuenta()
  } catch (e) {
    errores.value = [mensajeDeError(e)]
  }
}

async function guardarContrasena() {
  avisoCambio.value = null
  erroresCambio.value = []
  try {
    await cambiarContrasena(cambio.actual, cambio.nueva)
    cambio.actual = ''
    cambio.nueva = ''
    avisoCambio.value = 'Contraseña actualizada. Cerramos tus otras sesiones abiertas.'
  } catch (e) {
    erroresCambio.value = todosLosErrores(e)
  }
}

async function borrarCuenta() {
  try {
    await suprimirCuenta()
    sesion.olvidar()
    confirmandoBorrado.value = false
    modo.value = 'entrar'
    errores.value = []
    avisoCambio.value = null
    datos.value = null
  } catch (e) {
    erroresCambio.value = [mensajeDeError(e)]
  }
}

watch(autenticado, (si) => { if (si) void cargarDatos(); else datos.value = null }, { immediate: true })

function fecha(iso: string): string {
  return new Intl.DateTimeFormat('es-CO', { dateStyle: 'long' }).format(new Date(iso))
}
</script>

<template>
  <section aria-labelledby="titulo-cuenta">
    <h1 id="titulo-cuenta" class="mb-1 flex items-center gap-2 text-lg font-bold text-slate-900">
      <UserRound class="h-5 w-5 text-primary-600" aria-hidden="true" /> Tu cuenta
    </h1>

    <!-- Sin sesión: entrar o crear -->
    <template v-if="!autenticado">
      <p class="mb-4 text-xs text-slate-500">
        No necesitas cuenta para consultar ni reportar. Con una cuenta tus aportes suman reputación y tus reportes se
        validan más rápido.
      </p>

      <div class="mb-4 flex rounded-lg border border-slate-200 bg-white p-0.5" role="tablist" aria-label="Acceso">
        <button
          v-for="m in [{ id: 'entrar', texto: 'Entrar', icono: LogIn }, { id: 'crear', texto: 'Crear cuenta', icono: UserPlus }] as const"
          :key="m.id"
          type="button"
          role="tab"
          :aria-selected="modo === m.id"
          class="flex flex-1 items-center justify-center gap-1.5 rounded-md py-2 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-primary-500"
          :class="modo === m.id ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="modo = m.id; errores = []"
        >
          <component :is="m.icono" class="h-3.5 w-3.5" aria-hidden="true" /> {{ m.texto }}
        </button>
      </div>

      <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-4" novalidate @submit.prevent="enviar">
        <div>
          <label for="correo" class="mb-1 block text-sm font-semibold text-slate-800">Correo</label>
          <input
            id="correo" v-model="formulario.correo" type="email" autocomplete="email" inputmode="email" maxlength="120"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
          />
        </div>
        <div>
          <label for="contrasena" class="mb-1 block text-sm font-semibold text-slate-800">Contraseña</label>
          <div class="relative">
            <input
              id="contrasena" v-model="formulario.contrasena" :type="verContrasena ? 'text' : 'password'" maxlength="128"
              :autocomplete="modo === 'crear' ? 'new-password' : 'current-password'"
              aria-describedby="ayuda-contrasena"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
            />
            <button
              type="button" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-500 hover:text-slate-800"
              :aria-label="verContrasena ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="verContrasena"
              @click="verContrasena = !verContrasena"
            >
              <component :is="verContrasena ? EyeOff : Eye" class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
          <p v-if="modo === 'crear'" id="ayuda-contrasena" class="mt-1 text-xs text-slate-500">
            Mínimo {{ MINIMO_CONTRASENA }} caracteres. Una frase fácil de recordar funciona mejor que símbolos raros.
          </p>
        </div>

        <label v-if="modo === 'crear'" class="flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-700">
          <input v-model="formulario.acepta" type="checkbox" class="mt-0.5 h-4 w-4 accent-primary-600" />
          <span>
            Autorizo el tratamiento de mis datos personales según la
            <router-link to="/privacidad" class="font-semibold text-primary-700 underline">política de tratamiento de datos</router-link>
            (Ley 1581 de 2012).
          </span>
        </label>

        <ul v-if="errores.length" class="space-y-1 rounded-lg bg-danger-50 p-3 text-xs text-danger-700" role="alert">
          <li v-for="e in errores" :key="e">{{ e }}</li>
        </ul>

        <button
          type="submit" :disabled="!puedeEnviar || enviando"
          class="w-full rounded-lg bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 active:bg-primary-800 disabled:bg-slate-300"
        >
          {{ enviando ? 'Un momento…' : modo === 'crear' ? 'Crear cuenta' : 'Entrar' }}
        </button>
        <p class="text-center text-[11px] text-slate-500">Por seguridad, la sesión se cierra al recargar la página o a las 8 horas.</p>
      </form>
    </template>

    <!-- Con sesión: datos del titular (RF-23) -->
    <template v-else>
      <div class="mb-4 flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700" aria-hidden="true">
          {{ cuenta?.seudonimo.slice(-4) }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-slate-900">{{ cuenta?.seudonimo }}</p>
          <p class="truncate text-xs text-slate-500">{{ datos?.correo }}</p>
          <p v-if="cuenta?.rol === 'MODERADOR'" class="mt-1 inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700">
            <ShieldCheck class="h-3 w-3" aria-hidden="true" /> Moderador
          </p>
        </div>
        <button
          type="button" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
          @click="sesion.cerrar()"
        >
          <LogOut class="h-3.5 w-3.5" aria-hidden="true" /> Salir
        </button>
      </div>

      <router-link v-if="destino" :to="destino" class="mb-4 block rounded-lg bg-primary-600 py-2.5 text-center text-sm font-semibold text-white hover:bg-primary-700">
        Continuar
      </router-link>

      <p class="mb-4 text-xs text-slate-500">
        Tu seudónimo nunca se muestra junto a tus reportes.
        <template v-if="datos">Autorizaste el tratamiento de datos el {{ fecha(datos.consentimiento.fecha) }} (política {{ datos.consentimiento.versionPolitica }}).</template>
      </p>

      <form class="mb-4 space-y-3 rounded-xl border border-slate-200 bg-white p-4" @submit.prevent="guardarContrasena">
        <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-800"><KeyRound class="h-4 w-4 text-primary-600" aria-hidden="true" /> Cambiar contraseña</h2>
        <label class="block text-xs font-semibold text-slate-700">Contraseña actual
          <input v-model="cambio.actual" type="password" autocomplete="current-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100" />
        </label>
        <label class="block text-xs font-semibold text-slate-700">Nueva contraseña
          <input v-model="cambio.nueva" type="password" autocomplete="new-password" maxlength="128" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100" />
        </label>
        <p v-if="avisoCambio" class="rounded-lg bg-success-50 p-2 text-xs text-success-800" role="status">{{ avisoCambio }}</p>
        <ul v-if="erroresCambio.length" class="space-y-1 rounded-lg bg-danger-50 p-2 text-xs text-danger-700" role="alert">
          <li v-for="e in erroresCambio" :key="e">{{ e }}</li>
        </ul>
        <button type="submit" :disabled="!cambio.actual || cambio.nueva.length < MINIMO_CONTRASENA" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-900 disabled:bg-slate-300">
          Guardar
        </button>
      </form>

      <section class="rounded-xl border border-slate-200 bg-white p-4" aria-labelledby="titulo-borrar">
        <h2 id="titulo-borrar" class="mb-1 flex items-center gap-1.5 text-sm font-semibold text-danger-700"><Trash2 class="h-4 w-4" aria-hidden="true" /> Eliminar mi cuenta</h2>
        <p class="mb-3 text-xs text-slate-500">
          Borramos tu correo, tu contraseña y tu autorización. Los precios que reportaste se conservan, pero ya no
          quedan asociados a ninguna persona.
        </p>
        <button v-if="!confirmandoBorrado" type="button" class="rounded-lg border border-danger-600 px-3 py-1.5 text-xs font-semibold text-danger-700 hover:bg-danger-50" @click="confirmandoBorrado = true">
          Eliminar cuenta
        </button>
        <div v-else class="flex flex-wrap items-center gap-2" role="group" aria-label="Confirmar eliminación">
          <span class="text-xs font-semibold text-slate-800">¿Seguro? No se puede deshacer.</span>
          <button type="button" class="rounded-lg bg-danger-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-danger-700" @click="borrarCuenta">Sí, eliminar</button>
          <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100" @click="confirmandoBorrado = false">Cancelar</button>
        </div>
      </section>
    </template>
  </section>
</template>
