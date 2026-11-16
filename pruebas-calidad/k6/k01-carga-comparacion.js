// K-01 · EC-04 · M-05 (p95 ≤ 2 s) y M-06 (errores < 1 %)
//
// Usuarios concurrentes que consultan comparaciones de precios. Cada usuario virtual (VU) repite:
// consulta → pausa de 1 a 3 s (tiempo de lectura). Mezcla de consultas como las de la PWA:
//   70 % comparación por precio por unidad · 15 % comparación por distancia · 15 % resumen de 6 productos.
//
// Variables: VUS (500), RAMPA (1m), DURACION (3m; EC-04 pide 15m), BASE_URL.
import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { Rate } from 'k6/metrics';
import exec from 'k6/execution';

const BASE = __ENV.BASE_URL || 'http://api:8080/v1';
const VUS = parseInt(__ENV.VUS || '500', 10);
const productos = new SharedArray('productos', () => JSON.parse(open('/resultados/productos.json')));
const respuestasCorrectas = new Rate('respuestas_correctas');
const RAMPA = __ENV.RAMPA || '1m';
const DURACION = __ENV.DURACION || '3m';

function segundos(duracion) {
  const m = /^(\d+)(s|m|h)$/.exec(duracion);
  if (!m) throw new Error(`Duración no válida: ${duracion} (use 30s, 3m o 1h)`);
  return parseInt(m[1], 10) * { s: 1, m: 60, h: 3600 }[m[2]];
}
const INICIO_SOSTENIDA = segundos(RAMPA) * 1000;
const FIN_SOSTENIDA = INICIO_SOSTENIDA + segundos(DURACION) * 1000;

// M-05 se mide solo con los VUS completos (fase sostenida), no durante la rampa.
function fase() {
  const t = exec.instance.currentTestRunDuration;
  return t >= INICIO_SOSTENIDA && t < FIN_SOSTENIDA ? 'sostenida' : 'rampa';
}

export const options = {
  scenarios: {
    consulta_comparaciones: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: RAMPA, target: VUS },
        { duration: DURACION, target: VUS },
        { duration: '20s', target: 0 },
      ],
      gracefulRampDown: '10s',
    },
  },
  thresholds: {
    'http_req_duration{fase:sostenida}': ['p(95)<2000'], // M-05 aceptable
    http_req_failed: ['rate<0.01'], // M-06 aceptable
    respuestas_correctas: ['rate>0.99'],
    'http_reqs{fase:sostenida}': ['count>0'], // garantiza que la fase sostenida tuvo datos
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

function alAzar() {
  return productos[Math.floor(Math.random() * productos.length)];
}

export default function () {
  const r = Math.random();
  let res;
  if (r < 0.7) {
    res = http.get(`${BASE}/comparaciones?productoId=${alAzar()}`, { tags: { tipo: 'precio', fase: fase() } });
  } else if (r < 0.85) {
    const lat = (4.5 + Math.random() * 0.07).toFixed(4);
    const lon = (-75.72 + Math.random() * 0.08).toFixed(4);
    res = http.get(`${BASE}/comparaciones?productoId=${alAzar()}&orden=distancia&lat=${lat}&lon=${lon}`, {
      tags: { tipo: 'distancia', fase: fase() },
    });
  } else {
    const ids = Array.from({ length: 6 }, alAzar).join(',');
    res = http.get(`${BASE}/comparaciones/resumen?productoIds=${ids}`, { tags: { tipo: 'resumen', fase: fase() } });
  }

  const ok = check(res, {
    'estado 200': (x) => x.status === 200,
    'trae items': (x) => x.status === 200 && Array.isArray(x.json('items')),
  });
  respuestasCorrectas.add(ok);
  sleep(1 + Math.random() * 2);
}

export function handleSummary(data) {
  const m = data.metrics;
  const s = m['http_req_duration{fase:sostenida}'];
  const d = s ? s.values : {};
  const f = m.http_req_failed ? m.http_req_failed.values.rate : null;
  const txt = [
    `K-01 · ${VUS} VU · peticiones: ${m.http_reqs ? m.http_reqs.values.count : 0} (${m.http_reqs ? m.http_reqs.values.rate.toFixed(1) : 0}/s)`,
    `fase sostenida: p50 ${(d.med || 0).toFixed(0)} ms · p95 ${(d['p(95)'] || 0).toFixed(0)} ms · p99 ${(d['p(99)'] || 0).toFixed(0)} ms · máx ${(d.max || 0).toFixed(0)} ms`,
    `errores HTTP: ${f === null ? 'n/d' : (f * 100).toFixed(2) + ' %'}`,
    '',
  ].join('\n');
  return { '/resultados/k01-carga.json': JSON.stringify(data, null, 2), stdout: txt };
}
