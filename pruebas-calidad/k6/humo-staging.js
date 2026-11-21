// Prueba de humo tras cada despliegue (ADR-0020, ADR-0021 · EC-12, EC-04).
//
// Recorre los flujos de lectura que usa la PWA contra un entorno recién desplegado. No escribe datos:
// se puede correr contra staging y contra producción.
// Bloquea el pipeline si hay cualquier error (http_req_failed > 0 o un check falla) o si p95 > 2 s.
//
// Variables: API_URL (p. ej. https://api-staging-xxxx.up.railway.app/v1), WEB_URL (opcional), VUS (5), DURACION (30s).
import http from 'k6/http';
import { check, fail, sleep } from 'k6';

const API = __ENV.API_URL;
const WEB = __ENV.WEB_URL || '';
if (!API) throw new Error('Falta API_URL');

export const options = {
  vus: parseInt(__ENV.VUS || '5', 10),
  duration: __ENV.DURACION || '30s',
  thresholds: {
    http_req_failed: ['rate==0'],
    checks: ['rate==1'],
    http_req_duration: ['p(95)<2000'],
  },
};

const json = { headers: { Accept: 'application/json' } };

export function setup() {
  const r = http.get(`${API}/productos?tamano=5`, json);
  const items = r.status === 200 ? r.json('items') : [];
  if (!items || items.length === 0) fail(`El catálogo del entorno está vacío o no responde (HTTP ${r.status})`);
  // Unidad válida para la canasta según la unidad base del producto (KG → kg, L → L, UNIDAD → unidad).
  const UNIDAD = { KG: 'kg', L: 'L', UNIDAD: 'unidad' };
  const productos = items.map((p) => {
    const detalle = http.get(`${API}/productos/${p.id}`, json);
    return { id: p.id, unidad: UNIDAD[detalle.json('presentaciones.0.unidadBase')] || 'unidad' };
  });
  return { productos };
}

export default function ({ productos }) {
  const { id, unidad } = productos[__ITER % productos.length];

  const comparacion = http.get(`${API}/comparaciones?productoId=${id}`, json);
  check(comparacion, {
    'comparación 200': (r) => r.status === 200,
    'comparación declara criterioOrden (RN-09)': (r) => typeof r.json('criterioOrden.descripcion') === 'string',
    'comparación separa contenidoPromocional (EC-06)': (r) => Array.isArray(r.json('contenidoPromocional')),
  });

  check(http.get(`${API}/establecimientos?tamano=5`, json), { 'establecimientos 200': (r) => r.status === 200 });

  const canasta = http.post(
    `${API}/canastas/comparacion`,
    JSON.stringify({ orden: 'disponibilidad', items: [{ productoId: id, cantidad: '1', unidad }] }),
    { headers: { ...json.headers, 'Content-Type': 'application/json' } },
  );
  check(canasta, {
    'canasta 200': (r) => r.status === 200,
    'canasta separa contenidoPromocional (ADR-0019)': (r) => Array.isArray(r.json('contenidoPromocional')),
  });

  if (WEB) check(http.get(WEB), { 'PWA 200': (r) => r.status === 200 });
  sleep(1);
}
