// K-02 · EC-11 · M-16 (p95 ≤ 3 s; objetivo ≤ 1,5 s), errores < 1 % y M-17 (faltantes señalados y no sumados = 1)
//
// Usuarios concurrentes que comparan una canasta de 20 productos (el máximo, ADR-0017) contra los ~300
// establecimientos del volumen de carga. Cada usuario virtual (VU) repite: arma una canasta al azar → la compara →
// pausa de 2 a 5 s (revisar el resultado). La mitad ordena por disponibilidad y la otra mitad por total.
//
// M-17 se verifica en CADA respuesta: por establecimiento, disponibles + faltantes = totalProductos,
// completa ⇔ sin faltantes, una línea por producto disponible y total = suma de los subtotales
// (un faltante nunca se suma como cero, ADR-0019).
//
// Variables: VUS (200), RAMPA (1m), DURACION (3m), PRODUCTOS_POR_CANASTA (20), BASE_URL.
import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import { Rate } from 'k6/metrics';
import exec from 'k6/execution';

const BASE = __ENV.BASE_URL || 'http://api:8080/v1';
const VUS = parseInt(__ENV.VUS || '200', 10);
const RAMPA = __ENV.RAMPA || '1m';
const DURACION = __ENV.DURACION || '3m';
const TAMANO = parseInt(__ENV.PRODUCTOS_POR_CANASTA || '20', 10);
// Los productos del volumen de carga tienen unidad base KG (VolumenCargaSeeder).
const productos = new SharedArray('productos', () => JSON.parse(open('/resultados/productos.json')));
const faltantesCorrectos = new Rate('faltantes_correctos');
const respuestasCorrectas = new Rate('respuestas_correctas');

function segundos(duracion) {
  const m = /^(\d+)(s|m|h)$/.exec(duracion);
  if (!m) throw new Error(`Duración no válida: ${duracion} (use 30s, 3m o 1h)`);
  return parseInt(m[1], 10) * { s: 1, m: 60, h: 3600 }[m[2]];
}
const INICIO_SOSTENIDA = segundos(RAMPA) * 1000;
const FIN_SOSTENIDA = INICIO_SOSTENIDA + segundos(DURACION) * 1000;

// M-16 se mide solo con los VUS completos (fase sostenida), igual que M-05 en K-01.
function fase() {
  const t = exec.instance.currentTestRunDuration;
  return t >= INICIO_SOSTENIDA && t < FIN_SOSTENIDA ? 'sostenida' : 'rampa';
}

export const options = {
  scenarios: {
    comparar_canastas: {
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
    'http_req_duration{fase:sostenida}': ['p(95)<3000'], // M-16 aceptable
    http_req_failed: ['rate<0.01'],
    respuestas_correctas: ['rate>0.99'],
    faltantes_correctos: ['rate==1'], // M-17
    'http_reqs{fase:sostenida}': ['count>0'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

function canastaAlAzar() {
  const elegidos = new Set();
  while (elegidos.size < Math.min(TAMANO, productos.length)) {
    elegidos.add(productos[Math.floor(Math.random() * productos.length)]);
  }
  return [...elegidos].map((productoId) => ({ productoId, cantidad: String(1 + Math.floor(Math.random() * 3)), unidad: 'kg' }));
}

// Suma exacta en centésimos para comparar el total con los subtotales sin errores de coma flotante.
function centesimos(valor) {
  const [entero, dec = ''] = String(valor).split('.');
  const signo = entero.startsWith('-') ? -1 : 1;
  return signo * (Math.abs(parseInt(entero, 10)) * 100 + parseInt((dec + '00').slice(0, 2), 10));
}

function faltantesBienSenalados(cuerpo) {
  return cuerpo.establecimientos.every((e) =>
    e.disponibles + e.faltantes.length === e.totalProductos
    && e.completa === (e.faltantes.length === 0)
    && e.lineas.length === e.disponibles
    && Math.abs(e.lineas.reduce((s, l) => s + centesimos(l.subtotal), 0) - centesimos(e.total)) <= e.lineas.length);
}

export default function () {
  const items = canastaAlAzar();
  const orden = Math.random() < 0.5 ? 'disponibilidad' : 'total';
  const res = http.post(`${BASE}/canastas/comparacion`, JSON.stringify({ orden, items }), {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    tags: { orden, fase: fase() },
  });

  const ok = check(res, {
    'estado 200': (x) => x.status === 200,
    'trae establecimientos': (x) => x.status === 200 && Array.isArray(x.json('establecimientos')),
  });
  respuestasCorrectas.add(ok);
  if (ok) {
    const cuerpo = res.json();
    faltantesCorrectos.add(cuerpo.totalProductos === items.length && faltantesBienSenalados(cuerpo));
  }
  sleep(2 + Math.random() * 3);
}

export function handleSummary(data) {
  const m = data.metrics;
  const s = m['http_req_duration{fase:sostenida}'];
  const d = s ? s.values : {};
  const f = m.http_req_failed ? m.http_req_failed.values.rate : null;
  const fc = m.faltantes_correctos ? m.faltantes_correctos.values : null;
  const txt = [
    `K-02 · ${VUS} VU · canastas de ${TAMANO} · peticiones: ${m.http_reqs ? m.http_reqs.values.count : 0} (${m.http_reqs ? m.http_reqs.values.rate.toFixed(1) : 0}/s)`,
    `fase sostenida: p50 ${(d.med || 0).toFixed(0)} ms · p95 ${(d['p(95)'] || 0).toFixed(0)} ms · p99 ${(d['p(99)'] || 0).toFixed(0)} ms · máx ${(d.max || 0).toFixed(0)} ms`,
    `errores HTTP: ${f === null ? 'n/d' : (f * 100).toFixed(2) + ' %'} · M-17 faltantes correctos: ${fc ? `${fc.passes}/${fc.passes + fc.fails}` : 'n/d'}`,
    '',
  ].join('\n');
  return { '/resultados/k02-canasta.json': JSON.stringify(data, null, 2), stdout: txt };
}
