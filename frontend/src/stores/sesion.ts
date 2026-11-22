import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { fijarSesion } from '@/api/client'
import * as identidad from '@/api/identidad'
import type { CuentaPublica, RespuestaSesion } from '@/api/identidad'
import { VERSION_POLITICA } from '@/utils/privacidad'

/*
 * Sesión de la cuenta (ADR-0025). Todo vive en memoria: al recargar la página hay que volver a
 * entrar. Es el costo de no guardar el token en el navegador, donde un XSS podría leerlo.
 */
export const useSesionStore = defineStore('sesion', () => {
  const cuenta = ref<CuentaPublica | null>(null)
  const token = ref<string | null>(null)
  const expiraEn = ref<number>(0)

  const autenticado = computed(() => token.value !== null && Date.now() < expiraEn.value)
  const esModerador = computed(() => autenticado.value && cuenta.value?.rol === 'MODERADOR')

  function olvidar() {
    cuenta.value = null
    token.value = null
    expiraEn.value = 0
    fijarSesion(null, olvidar)
  }

  function abrir(r: RespuestaSesion) {
    cuenta.value = r.cuenta
    token.value = r.sesion.token
    expiraEn.value = Date.parse(r.sesion.expiraEn)
    fijarSesion(r.sesion.token, olvidar)
  }

  async function iniciar(correo: string, contrasena: string) {
    abrir(await identidad.iniciarSesion(correo.trim(), contrasena))
  }

  async function registrar(correo: string, contrasena: string, aceptaTratamientoDatos: boolean) {
    abrir(await identidad.registrarCuenta({ correo: correo.trim(), contrasena, aceptaTratamientoDatos, versionPolitica: VERSION_POLITICA }))
  }

  async function cerrar() {
    try {
      if (autenticado.value) await identidad.cerrarSesion()
    } catch {
      // Sin red el token igual vence a las 8 h; localmente se olvida ya.
    } finally {
      olvidar()
    }
  }

  function cabeceras(): Record<string, string> {
    return autenticado.value && token.value !== null ? { Authorization: `Bearer ${token.value}` } : {}
  }

  return { cuenta, autenticado, esModerador, iniciar, registrar, cerrar, olvidar, cabeceras }
})
