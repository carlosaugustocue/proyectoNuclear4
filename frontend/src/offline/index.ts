import { enviarDesdeCola } from '@/api/reportes'
import { ColaReportes, crearBaseCola } from './colaReportes'

/** Instancia única de la cola offline de la PWA (IndexedDB «mercadonorte-offline»). */
export const colaReportes = new ColaReportes(crearBaseCola(), enviarDesdeCola)
