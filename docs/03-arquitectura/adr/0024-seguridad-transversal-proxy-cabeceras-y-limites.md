# ADR-0024: Seguridad transversal: IP real tras el proxy, cabeceras de seguridad y límite de peticiones por IP

- **Estado:** Propuesto · **Fecha:** 2026-11-22
- **Responsable(s):** equipo (pruebas y CI: Carlos Augusto Aranzazu Jinete)
- **Escenarios relacionados:** EC-03, EC-04, EC-07
- **Relacionado con:** ADR-0007 (confianza), ADR-0013 (Railway), ADR-0021 (Trivy y SonarCloud)

## Contexto
Una revisión de seguridad del despliegue en Railway encontró tres huecos:

1. **La API no conocía la IP real del cliente.** Railway pone un proxy delante de la API y Laravel no confiaba en él, así que `request->ip()` devolvía la IP interna del proxy para todos. El seudónimo del reportante se calcula con esa IP, de modo que **todos los reportantes compartían el mismo seudónimo**. Consecuencias:
   - RN-05 (dos reportantes distintos) no podía cumplirse con reportes reales.
   - RN-07 (reputación) acumulaba los aportes de todos en una sola «cuenta».
   - El límite RN-08 (30 reportes por hora) era global para toda la ciudad.

   Las pruebas no lo detectaban porque simulan IP distintas directamente.
2. **Ninguna respuesta llevaba cabeceras de seguridad**, ni la PWA ni la API: sin CSP, HSTS, X-Frame-Options ni nosniff.
3. **No había límite general de peticiones.** RN-08 limita los reportes, pero las consultas no tenían tope ante abuso o raspado.

## Alternativas consideradas
| Tema | Alternativa | A favor | En contra |
|---|---|---|---|
| IP del cliente | A. Confiar en todos los proxies de la cadena | Simple | Un cliente puede **suplantar** a otro anteponiendo IP falsas en `X-Forwarded-For` |
| | **B. Confiar solo en el salto inmediato** (`trustProxies(at: '*')`, que en Laravel equivale a confiar en `REMOTE_ADDR`) | La IP es la última que agregó el proxy de Railway; lo que el cliente antepone se ignora | Si Railway agregara un segundo salto interno, habría que declararlo: se verifica en staging |
| | C. Leer `X-Real-IP` | Una sola cabecera | Laravel no la soporta de forma nativa; código propio sin beneficio |
| Cabeceras | **A. nginx (PWA) y middleware global (API)** | Cada contenedor declara las suyas; también cubren los errores (Problem Details) | Dos lugares que mantener |
| | B. Un proxy o CDN delante (Cloudflare) | Centralizado | Otro proveedor, costo y configuración fuera del repositorio (va contra R-04 y ADR-0011) |
| Límite | **A. Cupo por IP en la API, con lecturas y escrituras separadas y valores configurables** | Una IP que agota sus escrituras sigue consultando; el entorno de medición los sube | Usuarios detrás de la misma NAT comparten cupo (600 lecturas por minuto es holgado para eso) |
| | B. Límite en nginx | No llega a PHP | La PWA y la API son servicios distintos en Railway; la API no tiene nginx propio |

## Decisión
- **IP real:** se adopta B. `bootstrap/app.php` confía solo en el salto inmediato para `X-Forwarded-For` y `X-Forwarded-Proto`. Hay una prueba de regresión en C-18: dos clientes detrás del mismo proxy son dos reportantes, y anteponer IP falsas no cambia el seudónimo.
- **Cabeceras de la API** (middleware `CabecerasSeguridad`, global): `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'` (la API solo devuelve JSON), `Permissions-Policy`, `Cross-Origin-Resource-Policy: cross-origin` (la PWA vive en otro subdominio), HSTS en HTTPS y sin `X-Powered-By`.
- **Cabeceras de la PWA** (nginx, incluidas en cada `location`):
  - CSP con `script-src 'self'`, posible porque el build no genera scripts en línea.
  - `style-src 'unsafe-inline'`, porque Vue y Leaflet usan estilos en línea; esto no permite ejecutar código.
  - `img-src` limitado a Open Food Facts y a las teselas de OpenStreetMap.
  - `connect-src` con el origen de la API, calculado en el build a partir de `VITE_API_URL`.
  - `frame-ancestors 'none'`, `object-src 'none'`, `nosniff`, `DENY`, `Permissions-Policy` (la geolocalización solo se permite al propio origen), COOP, HSTS y `server_tokens off`.
- **Límite por IP:**
  - 600 lecturas y 60 escrituras por minuto, con cupos separados. Comparar una canasta cuenta como lectura aunque use POST.
  - Al superarlo, la API responde `429` con `Retry-After`, en formato Problem Details.
  - Los valores se cambian con `LIMITE_LECTURAS_POR_MINUTO` y `LIMITE_ESCRITURAS_POR_MINUTO`. El entorno de medición los sube, porque K-01 y K-02 salen de una sola IP.

## Consecuencias
- **Positivas:**
  - RN-05, RN-07 y RN-08 vuelven a funcionar con usuarios reales.
  - Desaparecen el *clickjacking*, el *sniffing* de tipos y la carga de scripts de terceros en la PWA.
  - El abuso de una IP queda acotado.
  - El CI verifica las cabeceras de la imagen construida (`scripts/ci/verificar-cabeceras-web.sh`).
- **Negativas:**
  - Los datos ya cargados en producción con el seudónimo compartido no se pueden separar por reportante. Afecta solo a reportes de prueba, porque la plataforma no tiene usuarios reales.
  - Agregar un dominio nuevo de imágenes o mapas exige actualizar la CSP.
  - La prueba de humo del pipeline (~410 peticiones en 30 s) queda por debajo del cupo; si se alarga, hay que tenerlo en cuenta.
- **Seguimiento:**
  - Después del despliegue, verificar en staging que dos clientes distintos producen seudónimos distintos y que anteponer IP falsas no cambia el seudónimo.
  - Correr el verificador de cabeceras contra staging y producción.
  - Pasar a Aceptado cuando el equipo lo revise.
