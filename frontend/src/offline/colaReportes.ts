import Dexie, { type EntityTable } from 'dexie'
import type { ReportePrecioNuevo } from '@/types'

/*
 * Cola offline de reportes (ADR-0006, EC-09, M-12).
 * Cada reporte se guarda con su Idempotency-Key ANTES de intentar enviarlo; los
 * reintentos usan siempre la misma clave, así el servidor nunca crea duplicados.
 */

export type ResultadoEnvio = 'aceptado' | 'rechazado' | 'sin-conexion'
export type Enviador = (cuerpo: ReportePrecioNuevo, claveIdempotencia: string) => Promise<ResultadoEnvio>

export interface ReportePendiente {
  claveIdempotencia: string
  cuerpo: ReportePrecioNuevo
  etiqueta: { producto: string; establecimiento: string }
  creadoEn: number
  estado: 'pendiente' | 'rechazado'
  intentos: number
}

export interface ResumenSincronizacion {
  enviados: number
  rechazados: number
  pendientes: number
}

export type BaseCola = Dexie & { reportes: EntityTable<ReportePendiente, 'claveIdempotencia'> }

export function crearBaseCola(nombre = 'mercadonorte-offline'): BaseCola {
  const db = new Dexie(nombre) as BaseCola
  db.version(1).stores({ reportes: 'claveIdempotencia, creadoEn, estado' })
  return db
}

export class ColaReportes {
  private sincronizando: Promise<ResumenSincronizacion> | null = null
  private readonly db: BaseCola
  private readonly enviar: Enviador

  constructor(db: BaseCola, enviar: Enviador) {
    this.db = db
    this.enviar = enviar
  }

  async encolar(cuerpo: ReportePrecioNuevo, etiqueta: ReportePendiente['etiqueta']): Promise<ReportePendiente> {
    const pendiente: ReportePendiente = {
      claveIdempotencia: crypto.randomUUID(),
      cuerpo: { ...cuerpo },
      etiqueta: { ...etiqueta },
      creadoEn: Date.now(),
      estado: 'pendiente',
      intentos: 0,
    }
    await this.db.reportes.add(pendiente)
    return pendiente
  }

  listar(): Promise<ReportePendiente[]> {
    return this.db.reportes.orderBy('creadoEn').toArray()
  }

  contarPendientes(): Promise<number> {
    return this.db.reportes.where('estado').equals('pendiente').count()
  }

  descartar(claveIdempotencia: string): Promise<void> {
    return this.db.reportes.delete(claveIdempotencia)
  }

  /** Evita dos rondas simultáneas (p. ej., evento `online` + botón «Reintentar»). */
  sincronizar(): Promise<ResumenSincronizacion> {
    this.sincronizando ??= this.ronda().finally(() => {
      this.sincronizando = null
    })
    return this.sincronizando
  }

  private async ronda(): Promise<ResumenSincronizacion> {
    let enviados = 0
    let rechazados = 0
    const pendientes = (await this.listar()).filter((r) => r.estado === 'pendiente')

    for (const reporte of pendientes) {
      const resultado = await this.enviar(reporte.cuerpo, reporte.claveIdempotencia)
      if (resultado === 'sin-conexion') {
        await this.db.reportes.update(reporte.claveIdempotencia, { intentos: reporte.intentos + 1 })
        break // sin red: no tiene sentido seguir en esta ronda
      }
      if (resultado === 'aceptado') {
        await this.db.reportes.delete(reporte.claveIdempotencia)
        enviados++
      } else {
        await this.db.reportes.update(reporte.claveIdempotencia, { estado: 'rechazado', intentos: reporte.intentos + 1 })
        rechazados++
      }
    }

    return { enviados, rechazados, pendientes: await this.contarPendientes() }
  }
}
