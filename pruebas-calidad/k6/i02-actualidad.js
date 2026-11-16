// I-02 · EC-02 · M-02 (latencia de actualidad: aceptable ≤ 5 min, objetivo ≤ 1 min)
//
// Mide cuánto tarda la comparación en dejar de mostrar el precio anterior después de que llega uno nuevo,
// pasando por el flujo real y asíncrono: POST /reportes-precio → outbox → worker (cola domain-events) →
// Confianza → proyección comparacion_vigente → GET /comparaciones.
//
// Un solo usuario cambia N veces el precio de una presentación en un establecimiento (+0,2 % cada vez, para
// que ninguna regla lo trate como anomalía) y consulta cada 200 ms hasta ver el precio nuevo.
// Resolución de la medida: ~200 ms. Variables: PRODUCTO_ID, PRESENTACION_ID, ESTABLECIMIENTO_ID,
// PRECIO_BASE, CAMBIOS (20), LIMITE_S (600).
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const BASE = __ENV.BASE_URL || 'http://api:8080/v1';
const CAMBIOS = parseInt(__ENV.CAMBIOS || '20', 10);
const LIMITE_MS = parseInt(__ENV.LIMITE_S || '600', 10) * 1000;
const latencia = new Trend('latencia_actualidad', true);
const sinActualizar = new Counter('cambios_sin_actualizar');

export const options = {
  scenarios: { cambios: { executor: 'per-vu-iterations', vus: 1, iterations: CAMBIOS, maxDuration: '2h' } },
  thresholds: {
    latencia_actualidad: ['max<300000'], // M-02 aceptable: ≤ 5 min en TODOS los cambios
    cambios_sin_actualizar: ['count==0'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'max'],
};

function uuid() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
  });
}

function precioMostrado() {
  const res = http.get(
    `${BASE}/comparaciones?productoId=${__ENV.PRODUCTO_ID}&presentacionId=${__ENV.PRESENTACION_ID}`,
    { tags: { name: 'consulta' } },
  );
  if (res.status !== 200) return null;
  const item = (res.json('items') || []).find((i) => i.establecimiento && i.establecimiento.id === __ENV.ESTABLECIMIENTO_ID);
  return item ? parseFloat(item.precio) : null;
}

export default function () {
  const i = exec_iteracion();
  const nuevo = Math.round(parseFloat(__ENV.PRECIO_BASE) * (1 + 0.002 * (i + 1)));
  const t0 = Date.now();
  const res = http.post(
    `${BASE}/reportes-precio`,
    JSON.stringify({
      presentacionId: __ENV.PRESENTACION_ID,
      establecimientoId: __ENV.ESTABLECIMIENTO_ID,
      precio: String(nuevo),
      observadoEn: new Date(t0 - 1000).toISOString(),
    }),
    { headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'Idempotency-Key': uuid() }, tags: { name: 'reporte' } },
  );
  if (!check(res, { 'reporte aceptado (202)': (r) => r.status === 202 })) {
    console.error(`Reporte rechazado: ${res.status} ${res.body}`);
    sinActualizar.add(1);
    return;
  }

  while (Date.now() - t0 < LIMITE_MS) {
    if (precioMostrado() === nuevo) {
      latencia.add(Date.now() - t0);
      sleep(2);
      return;
    }
    sleep(0.2);
  }
  sinActualizar.add(1);
}

let iteracion = -1;
function exec_iteracion() {
  iteracion += 1;
  return iteracion;
}

export function handleSummary(data) {
  const l = data.metrics.latencia_actualidad;
  const v = l ? l.values : {};
  const n = data.metrics.cambios_sin_actualizar ? data.metrics.cambios_sin_actualizar.values.count : 0;
  const txt = `I-02 · ${CAMBIOS} cambios · latencia p50 ${(v.med || 0).toFixed(0)} ms · p95 ${(v['p(95)'] || 0).toFixed(0)} ms · máx ${(v.max || 0).toFixed(0)} ms · sin actualizar: ${n}\n`;
  return { '/resultados/i02-actualidad.json': JSON.stringify(data, null, 2), stdout: txt };
}
