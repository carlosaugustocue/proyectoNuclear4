import apiClient from './client'

/* Identidad — RF-22 a RF-24, ADR-0025 (contrato: /cuentas y /sesiones). */
export type Rol = 'CIUDADANO' | 'MODERADOR'

export interface CuentaPublica {
  id: string
  seudonimo: string
  rol: Rol
}

export interface RespuestaSesion {
  cuenta: CuentaPublica
  sesion: { token: string; expiraEn: string }
}

export interface MiCuenta extends CuentaPublica {
  correo: string
  estado: 'ACTIVA' | 'SUSPENDIDA'
  consentimiento: { fecha: string; versionPolitica: string }
}

export interface NuevaCuenta {
  correo: string
  contrasena: string
  aceptaTratamientoDatos: boolean
  versionPolitica: string
}

export async function registrarCuenta(datos: NuevaCuenta): Promise<RespuestaSesion> {
  const { data } = await apiClient.post<RespuestaSesion>('/cuentas', datos)
  return data
}

export async function iniciarSesion(correo: string, contrasena: string): Promise<RespuestaSesion> {
  const { data } = await apiClient.post<RespuestaSesion>('/sesiones', { correo, contrasena })
  return data
}

export async function cerrarSesion(): Promise<void> {
  await apiClient.delete('/sesiones/actual')
}

export async function miCuenta(): Promise<MiCuenta> {
  const { data } = await apiClient.get<MiCuenta>('/cuentas/yo')
  return data
}

export async function cambiarContrasena(actual: string, nueva: string): Promise<void> {
  await apiClient.put('/cuentas/yo/contrasena', { actual, nueva })
}

export async function suprimirCuenta(): Promise<void> {
  await apiClient.delete('/cuentas/yo')
}
