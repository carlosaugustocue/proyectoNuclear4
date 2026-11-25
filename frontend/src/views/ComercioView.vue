<script setup lang="ts">
/*
 * Vista Comercio (corte 3; RF-31 a RF-34). Un comercio solicita su perfil y, cuando un moderador lo
 * verifica, usa su credencial (solo en memoria, ADR-0022) para publicar un precio oficial (pasa por las
 * reglas de confianza, ADR-0015), promociones (bloque aparte, RN-14/RN-16) y solicitudes de corrección.
 */
import { computed, reactive, ref } from 'vue'
import { BadgeCheck, LogOut, Megaphone, MessageSquareWarning, Search, Store, Tag } from 'lucide-vue-next'
import { buscarEstablecimientos } from '@/api/establecimientos'
import { buscarProductos, obtenerProducto } from '@/api/catalogo'
import { publicarPrecioOficial, publicarPromocion, retirarPromocion, solicitarCorreccion, solicitarPerfil, type TipoPromocion } from '@/api/comercial'
import { mensajeDeError, todosLosErrores } from '@/api/client'
import { LONGITUD_CREDENCIAL, useComercioStore, type EstablecimientoComercio } from '@/stores/comercio'
import { describirPresentacion } from '@/utils/catalogo'
import type { Establecimiento, Presentacion, ProductoResumen } from '@/types'

const comercio = useComercioStore()

// --- Establecimiento (para solicitar el perfil o iniciar sesión) ---
const textoEstab = ref('')
const establecimientos = ref<Establecimiento[]>([])
const elegido = ref<EstablecimientoComercio | null>(null)
async function buscarEstab() {
  establecimientos.value = textoEstab.value.trim().length < 2 ? [] : (await buscarEstablecimientos({ q: textoEstab.value.trim(), tamano: 6 })).items
}
function elegirEstab(e: Establecimiento) {
  elegido.value = { id: e.id, nombre: e.nombre }
  establecimientos.value = []
  textoEstab.value = e.nombre
}

// --- Solicitud de perfil ---
const solicitud = reactive({ nombreContacto: '', telefonoContacto: '' })
const perfilSolicitado = ref<string | null>(null)
const errorSolicitud = ref<string | null>(null)
async function enviarSolicitud() {
  if (!elegido.value) return
  errorSolicitud.value = null
  try {
    perfilSolicitado.value = (await solicitarPerfil({
      establecimientoId: elegido.value.id,
      nombreContacto: solicitud.nombreContacto,
      telefonoContacto: solicitud.telefonoContacto || undefined,
    })).perfilId
  } catch (e) {
    errorSolicitud.value = mensajeDeError(e)
  }
}

// --- Sesión ---
const credencial = ref('')
const errorSesion = ref<string | null>(null)
function entrar() {
  errorSesion.value = elegido.value && comercio.iniciarSesion(credencial.value, elegido.value)
    ? null
    : `Elige tu establecimiento y pega la credencial de ${LONGITUD_CREDENCIAL} caracteres que te entregó el moderador.`
  credencial.value = ''
}

// --- Producto (compartido por precio oficial y promoción) ---
const textoProd = ref('')
const productos = ref<ProductoResumen[]>([])
const producto = ref<ProductoResumen | null>(null)
const presentaciones = ref<Presentacion[]>([])
async function buscarProd() {
  productos.value = textoProd.value.trim().length < 2 ? [] : (await buscarProductos({ q: textoProd.value.trim(), tamano: 6 })).items
}
async function elegirProducto(p: ProductoResumen) {
  producto.value = p
  productos.value = []
  textoProd.value = p.nombre
  presentaciones.value = (await obtenerProducto(p.id)).presentaciones
  precio.presentacionId = presentaciones.value[0]?.id ?? ''
}

// --- Publicaciones ---
const aviso = ref<string | null>(null)
const errores = ref<string[]>([])
const precio = reactive({ presentacionId: '', monto: '' })
const promo = reactive({ tipo: 'DESCUENTO_PORCENTUAL' as TipoPromocion, valor: '', pague: 2, lleve: 3, inicio: '', fin: '' })
const correccion = ref('')
const puedePublicar = computed(() => comercio.autenticado && producto.value !== null)

async function ejecutar(accion: () => Promise<string>) {
  aviso.value = null
  errores.value = []
  try {
    aviso.value = await accion()
  } catch (e) {
    errores.value = todosLosErrores(e)
  }
}

function establecimientoActual(): string {
  return comercio.establecimiento?.id ?? ''
}

const enviarPrecio = () => ejecutar(async () => {
  await publicarPrecioOficial(comercio.cabeceras(), {
    presentacionId: precio.presentacionId, establecimientoId: establecimientoActual(), precio: precio.monto,
  })
  return 'Precio oficial recibido. Se evalúa con las mismas reglas de confianza que cualquier reporte.'
})

const enviarPromocion = () => ejecutar(async () => {
  if (!producto.value) return 'Elige un producto.'
  const esPagueLleve = promo.tipo === 'PAGUE_X_LLEVE_Y'
  const creada = await publicarPromocion(comercio.cabeceras(), {
    establecimientoId: establecimientoActual(),
    productoId: producto.value.id,
    tipo: promo.tipo,
    ...(esPagueLleve ? { pague: promo.pague, lleve: promo.lleve } : { valor: promo.valor }),
    inicio: new Date(`${promo.inicio}T00:00:00`).toISOString(),
    fin: new Date(`${promo.fin}T23:59:59`).toISOString(),
  })
  comercio.registrarPromocion({ ...creada, producto: producto.value.nombre })
  return `Promoción publicada: ${creada.descripcion}. Se mostrará aparte, sin cambiar el orden.`
})

const retirar = (promocionId: string) => ejecutar(async () => {
  await retirarPromocion(comercio.cabeceras(), promocionId)
  comercio.quitarPromocion(promocionId)
  return 'Promoción retirada.'
})

const enviarCorreccion = () => ejecutar(async () => {
  await solicitarCorreccion(comercio.cabeceras(), { establecimientoId: establecimientoActual(), detalle: correccion.value })
  correccion.value = ''
  return 'Solicitud enviada a moderación.'
})

const campo = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200'
const boton = 'rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50'
</script>

<template>
  <section aria-labelledby="titulo-comercio">
    <header class="mb-4 flex items-center gap-2">
      <Store class="h-6 w-6 text-primary-600" aria-hidden="true" />
      <div>
        <h1 id="titulo-comercio" class="text-lg font-bold text-slate-900">Soy un comercio</h1>
        <p class="text-xs text-slate-500">Tus publicaciones nunca cambian el orden de la comparación.</p>
      </div>
    </header>

    <!-- Sin sesión: elegir establecimiento, solicitar perfil o entrar con credencial -->
    <template v-if="!comercio.autenticado">
      <label for="buscar-estab" class="mb-1 block text-xs font-semibold text-slate-600">Tu establecimiento</label>
      <input id="buscar-estab" v-model="textoEstab" type="search" :class="campo" placeholder="Nombre del establecimiento" @input="buscarEstab" />
      <ul v-if="establecimientos.length" class="mt-1 divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white" aria-label="Establecimientos encontrados">
        <li v-for="e in establecimientos" :key="e.id">
          <button type="button" class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="elegirEstab(e)">{{ e.nombre }}</button>
        </li>
      </ul>
      <p v-if="elegido" class="mt-1 text-xs text-success-700">Elegido: {{ elegido.nombre }}</p>

      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <form class="rounded-xl border border-slate-200 bg-white p-3" aria-labelledby="titulo-solicitar" @submit.prevent="enviarSolicitud">
          <h2 id="titulo-solicitar" class="mb-2 text-sm font-semibold text-slate-800">1. Solicitar perfil</h2>
          <label for="contacto" class="text-xs text-slate-600">Nombre de contacto (privado)</label>
          <input id="contacto" v-model="solicitud.nombreContacto" required maxlength="120" :class="campo" />
          <label for="telefono" class="mt-2 block text-xs text-slate-600">Teléfono (privado, opcional)</label>
          <input id="telefono" v-model="solicitud.telefonoContacto" maxlength="30" inputmode="tel" :class="campo" />
          <button type="submit" :class="[boton, 'mt-3 w-full']" :disabled="!elegido">Solicitar</button>
          <p v-if="perfilSolicitado" class="mt-2 text-xs text-slate-600" role="status">
            Solicitud recibida. Número de solicitud: <code class="break-all font-mono">{{ perfilSolicitado }}</code>. Un moderador verificará que representas al establecimiento y te entregará una credencial.
          </p>
          <p v-if="errorSolicitud" class="mt-2 text-xs text-danger-700" role="alert">{{ errorSolicitud }}</p>
        </form>

        <form class="rounded-xl border border-slate-200 bg-white p-3" aria-labelledby="titulo-entrar" @submit.prevent="entrar">
          <h2 id="titulo-entrar" class="mb-2 text-sm font-semibold text-slate-800">2. Ya tengo credencial</h2>
          <label for="credencial" class="text-xs text-slate-600">Credencial de comercio</label>
          <input id="credencial" v-model="credencial" type="password" autocomplete="off" :class="campo" />
          <p class="mt-1 text-[11px] text-slate-500">No se guarda en este dispositivo: al recargar tendrás que pegarla de nuevo.</p>
          <button type="submit" :class="[boton, 'mt-3 w-full']">Entrar</button>
          <p v-if="errorSesion" class="mt-2 text-xs text-danger-700" role="alert">{{ errorSesion }}</p>
        </form>
      </div>
    </template>

    <!-- Con sesión -->
    <template v-else>
      <div class="mb-4 flex items-center justify-between rounded-xl bg-success-50 px-3 py-2">
        <p class="flex items-center gap-1.5 text-sm font-semibold text-success-800"><BadgeCheck class="h-4 w-4" aria-hidden="true" /> {{ comercio.establecimiento?.nombre }}</p>
        <button type="button" class="flex items-center gap-1 text-xs text-slate-600 hover:text-danger-700" @click="comercio.cerrarSesion()">
          <LogOut class="h-3.5 w-3.5" aria-hidden="true" /> Salir
        </button>
      </div>

      <div role="status" aria-live="polite">
        <p v-if="aviso" class="mb-3 rounded-lg bg-success-50 p-2.5 text-sm text-success-800">{{ aviso }}</p>
      </div>
      <ul v-if="errores.length" class="mb-3 list-inside list-disc rounded-lg bg-danger-50 p-2.5 text-sm text-danger-700" role="alert">
        <li v-for="e in errores" :key="e">{{ e }}</li>
      </ul>

      <label for="buscar-prod" class="mb-1 block text-xs font-semibold text-slate-600">Producto</label>
      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" aria-hidden="true" />
        <input id="buscar-prod" v-model="textoProd" type="search" :class="[campo, 'pl-9']" placeholder="Busca en el catálogo" @input="buscarProd" />
      </div>
      <ul v-if="productos.length" class="mt-1 divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white" aria-label="Productos encontrados">
        <li v-for="p in productos" :key="p.id">
          <button type="button" class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="elegirProducto(p)">{{ p.nombre }}</button>
        </li>
      </ul>

      <div class="mt-4 space-y-4">
        <form class="rounded-xl border border-slate-200 bg-white p-3" aria-labelledby="titulo-precio" @submit.prevent="enviarPrecio">
          <h2 id="titulo-precio" class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-slate-800"><Tag class="h-4 w-4" aria-hidden="true" /> Precio oficial</h2>
          <div class="flex gap-2">
            <label class="sr-only" for="presentacion">Presentación</label>
            <select id="presentacion" v-model="precio.presentacionId" :class="campo" :disabled="!presentaciones.length">
              <option v-for="pr in presentaciones" :key="pr.id" :value="pr.id">{{ describirPresentacion(pr) }}</option>
            </select>
            <label class="sr-only" for="monto">Precio en pesos</label>
            <input id="monto" v-model="precio.monto" inputmode="decimal" placeholder="$" required :class="[campo, 'w-28']" />
          </div>
          <button type="submit" :class="[boton, 'mt-3']" :disabled="!puedePublicar || !precio.presentacionId">Publicar precio</button>
        </form>

        <form class="rounded-xl border border-slate-200 bg-white p-3" aria-labelledby="titulo-promo" @submit.prevent="enviarPromocion">
          <h2 id="titulo-promo" class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-slate-800"><Megaphone class="h-4 w-4" aria-hidden="true" /> Promoción</h2>
          <label for="tipo" class="text-xs text-slate-600">Tipo</label>
          <select id="tipo" v-model="promo.tipo" :class="campo">
            <option value="DESCUENTO_PORCENTUAL">Descuento porcentual</option>
            <option value="PRECIO_ESPECIAL">Precio especial</option>
            <option value="PAGUE_X_LLEVE_Y">Pague X, lleve Y</option>
          </select>
          <div v-if="promo.tipo === 'PAGUE_X_LLEVE_Y'" class="mt-2 flex gap-2">
            <label class="flex-1 text-xs text-slate-600">Pague <input v-model.number="promo.pague" type="number" min="1" :class="campo" /></label>
            <label class="flex-1 text-xs text-slate-600">Lleve <input v-model.number="promo.lleve" type="number" min="2" :class="campo" /></label>
          </div>
          <label v-else class="mt-2 block text-xs text-slate-600">
            {{ promo.tipo === 'DESCUENTO_PORCENTUAL' ? 'Porcentaje (1 a 90)' : 'Precio especial en pesos' }}
            <input v-model="promo.valor" inputmode="decimal" required :class="campo" />
          </label>
          <div class="mt-2 flex gap-2">
            <label class="flex-1 text-xs text-slate-600">Desde <input v-model="promo.inicio" type="date" required :class="campo" /></label>
            <label class="flex-1 text-xs text-slate-600">Hasta (máx. 31 días) <input v-model="promo.fin" type="date" required :class="campo" /></label>
          </div>
          <button type="submit" :class="[boton, 'mt-3']" :disabled="!puedePublicar">Publicar promoción</button>

          <ul v-if="comercio.promociones.length" class="mt-3 space-y-1.5" aria-label="Promociones publicadas en esta sesión">
            <li v-for="p in comercio.promociones" :key="p.promocionId" class="flex items-center justify-between rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs">
              <span>{{ p.descripcion }} · {{ p.producto }}</span>
              <button type="button" class="font-semibold text-danger-700 hover:underline" @click="retirar(p.promocionId)">Retirar</button>
            </li>
          </ul>
        </form>

        <form class="rounded-xl border border-slate-200 bg-white p-3" aria-labelledby="titulo-correccion" @submit.prevent="enviarCorreccion">
          <h2 id="titulo-correccion" class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-slate-800"><MessageSquareWarning class="h-4 w-4" aria-hidden="true" /> Corregir información de mi negocio</h2>
          <label for="detalle" class="sr-only">Qué hay que corregir</label>
          <textarea id="detalle" v-model="correccion" rows="3" minlength="10" maxlength="150" required :class="campo" placeholder="Ej.: la dirección cambió a…" />
          <button type="submit" :class="[boton, 'mt-2']">Enviar a moderación</button>
        </form>
      </div>
    </template>
  </section>
</template>
