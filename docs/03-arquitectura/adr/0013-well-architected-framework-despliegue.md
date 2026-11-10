# ADR-0013: Referencia al Well-Architected Framework y proveedor de despliegue en nube

- **Estado:** Aceptado
- **Fecha:** 2026-11-10
- **Responsables:** Carlos Augusto Aranzazu Jinete · Miguel Ángel Rodríguez · Sofía Giraldo González
- **Escenarios relacionados:** EC-04 (desempeño), EC-09 (tolerancia a fallos), EC-07 (seguridad/confidencialidad), R-04 (presupuesto académico), RDA3 de Arquitectura de Software II (cloud native y orquestación)

## Contexto

La guía §8 (Fase 3) exige que el equipo decida el proveedor, el modelo de servicio en la nube y justifique la decisión. La asignatura Arquitectura de Software II (Moodle, tema "Arquitecto Cloud") requiere además comparar los **Well-Architected Frameworks (WAF)** de los tres grandes proveedores — AWS, Microsoft Azure y Google Cloud — y argumentar cuál se ajusta mejor al proyecto.

El proyecto tiene restricciones concretas que acotan la decisión:

- **R-04:** equipo de 3 personas, presupuesto de nube gratuito o académico.
- **ADR-0011:** despliegue basado en Docker y `docker compose`; el sistema debe poder levantarse con un comando en cualquier máquina.
- **ADR-0012:** stack PHP 8.3 + Laravel (API) + Vue 3 (PWA); no hay dependencias de SDK propietario de ningún proveedor.
- **500 usuarios concurrentes** (EC-04): carga modesta para cualquier PaaS actual.
- **Datos personales colombianos** (R-01, Ley 1581): el proveedor elegido debe ofrecer región en Latinoamérica o garantías de transferencia internacional adecuadas.

La elección se divide en dos sub-decisiones relacionadas:
1. **¿Qué WAF se adopta como marco de referencia para las decisiones de operación y calidad en nube?**
2. **¿En qué proveedor/plataforma se desplegará el sistema?**

## Comparación de los tres Well-Architected Frameworks

### Pilares de cada WAF y su relevancia para el proyecto

| Pilar | AWS WAF (6 pilares) | Azure WAF (5 pilares) | Google Cloud Architecture Framework (6 pilares) | Relevancia en el proyecto |
|---|---|---|---|---|
| **Excelencia operacional** | Sí — automatización de operaciones, CI/CD, observabilidad | Sí — DevOps, monitoreo | Sí — automatización y gestión | Alta: GitHub Actions + pipeline CI (ADR-0011) |
| **Seguridad** | Sí — identidad, protección de datos, detección de amenazas | Sí — identidad, privacidad, cumplimiento normativo | Sí — seguridad y privacidad/cumplimiento | Alta: EC-07, Ley 1581 (R-01) |
| **Fiabilidad / Confiabilidad** | Sí — recuperación ante fallos, escalado, gestión del cambio | Sí — diseño para fallos, recuperación | Sí — confiabilidad | Alta: EC-09 (offline-first + sincronización) |
| **Eficiencia de desempeño / Rendimiento** | Sí — tipos de recursos, escalado, monitoreo | Sí — escalabilidad horizontal/vertical | Sí — rendimiento y optimización de costos (pilar conjunto) | Alta: EC-04 (500 VU, p95 ≤ 2 s) |
| **Optimización de costos** | Sí — pilar independiente con modelo de precios | Sí — gestión de costos, gobierno | Sí — junto con rendimiento | Crítica: R-04 (presupuesto académico/gratuito) |
| **Sostenibilidad** | Sí (6.° pilar, 2021) — huella de carbono, eficiencia energética | No pilar explícito | Implícito en "Managed Services" | Baja: no es un escenario del proyecto |
| **Servicios gestionados** | Implícito en eficiencia | Implícito | Sí — pilar explícito que incentiva uso de servicios GCP | Baja-Media: se quiere docker compose portátil (ADR-0011) |

### Análisis comparativo por criterio del proyecto

| Criterio | AWS WAF | Azure WAF | Google Cloud Architecture Framework |
|---|---|---|---|
| **Cobertura académica y documentación** | Muy alta — el WAF más citado en literatura académica; AWS Well-Architected Tool gratuito | Alta — Microsoft Learn, documentación extensa | Media — menos presente en universidades colombianas |
| **Alineación con GitHub Actions (ADR-0011)** | Buena (AWS CodePipeline es alternativa, pero GitHub Actions se integra bien) | **Muy alta** — GitHub es propiedad de Microsoft; native runners y secrets integrados | Buena |
| **Presupuesto académico (R-04)** | AWS Educate / AWS Academy (créditos) — disponible pero proceso de solicitud variable | **Azure for Students: USD 100/año sin tarjeta de crédito** — más accesible | Google for Education / créditos de investigación — proceso más complejo |
| **Hosting de contenedores Docker** | ECS Fargate, App Runner, Elastic Beanstalk | Azure Container Apps, Azure App Service | Cloud Run — excelente para contenedores stateless |
| **PostgreSQL gestionado** | Amazon RDS for PostgreSQL (tiene free tier limitado) | Azure Database for PostgreSQL Flexible Server (incluido en créditos) | Cloud SQL for PostgreSQL |
| **Principio de menor complejidad (R-04)** | Medio — muchos servicios, curva de configuración | **Alto** — Portal unificado, CLI clara, integración VS Code | Medio |
| **Región más cercana a Colombia** | us-east-1 (Virginia), us-east-2 (Ohio) — latencia ~80 ms | eastus / eastus2 (Virginia) — latencia ~80 ms; **Brasil (brazilsouth)** ~25 ms | us-central1 (Iowa) — latencia ~100 ms; Southamerica-east1 (São Paulo) ~30 ms |
| **Cobertura del pilar de Privacidad/Cumplimiento** | Buena — AWS Artifact, IAM granular | **Muy buena** — Microsoft Purview, certificaciones ISO 27001/27018 explícitas, GDPR como referencia para Ley 1581 | Buena — Google Cloud Compliance |

### Decisión 1: Framework de referencia

**Se adopta el AWS Well-Architected Framework como marco de referencia** para las decisiones de arquitectura en nube del proyecto.

**Justificación:**
- Es el WAF con mayor cobertura académica y el más citado en la bibliografía de Arquitectura de Software (Richards & Ford, 2020; Bass et al., 2021). Facilita la argumentación en la sustentación.
- Sus 6 pilares cubren todos los escenarios de calidad del proyecto: Excelencia Operacional (CI/CD, ADR-0011), Seguridad (EC-07, R-01), Fiabilidad (EC-09), Eficiencia de Desempeño (EC-04), Optimización de Costos (R-04) y Sostenibilidad (considerada pero de baja prioridad).
- El pilar de **Optimización de Costos** tiene principios más detallados que los otros WAF para equipos pequeños: *right-sizing*, uso de instancias spot/preemptible, y arquitecturas serverless opcionales — todos relevantes para R-04.
- **Adoptar el WAF no implica desplegar en AWS**: el marco es un conjunto de principios agnósticos al proveedor; el despliegue real se decide por separado (ver Decisión 2).

**Por qué no Azure WAF como marco:**
Azure WAF es muy sólido y su integración con GitHub Actions es superior, pero el AWS WAF tiene mayor peso en la literatura académica que cita el docente (Bass, Clements & Kazman, 2021). Se registra la ventaja de Azure en Decisión 2.

**Por qué no Google Cloud Architecture Framework:**
Su pilar de "Managed Services" empuja hacia servicios propietarios de GCP, lo que contradiría la portabilidad de ADR-0011 (docker compose agnóstico). Además tiene menor presencia en el contexto académico colombiano.

### Aplicación de los pilares AWS WAF al proyecto

| Pilar AWS WAF | Principios aplicados al proyecto | Evidencia / Verificación |
|---|---|---|
| **Excelencia operacional** | Pipeline CI en GitHub Actions con puertas de calidad (Pest, PHPStan, Deptrac, Spectral); commits convencionales; ADRs como registro de decisiones; retrospectiva por corte | Pipeline en verde; historial de ADRs |
| **Seguridad** | JWT para autenticación; sin datos personales en respuestas públicas (EC-07); seudónimos; cumplimiento Ley 1581 (R-01); HTTPS obligatorio; secretos en variables de entorno, nunca en el repositorio | Prueba C-07; análisis PHPStan nivel 8; secretos en GitHub Secrets |
| **Fiabilidad** | PWA offline-first con `BackgroundSync` (EC-09); `Idempotency-Key` en todos los POST de registro; tabla `outbox` con reintento automático; respaldo de PostgreSQL en volumen persistente | Prueba S-09; prueba I-09 (idempotencia) |
| **Eficiencia de desempeño** | Modelo de lectura desnormalizado `comparacion_vigente` (ADR-0003); índices espaciales PostGIS para búsquedas por radio; p95 ≤ 2 s con 500 VU (M-05) | Prueba k6 K-01 |
| **Optimización de costos** | 1 imagen Docker base para API y Worker (*right-sizing*); docker compose sin orquestador adicional; proveedor con free tier (ver Decisión 2); sin servicios gestionados propietarios | Factura mensual ≤ USD 0 durante el semestre |
| **Sostenibilidad** | Contenedores compactos (php-fpm alpino + nginx alpino); sin recursos inactivos sobredimensionados; apagado fuera de horario académico en entorno de demo | No se mide formalmente; principio de diseño |

---

## Alternativas de proveedor de despliegue consideradas

| Proveedor / Plataforma | Modelo | Free tier / Créditos | Soporte Docker Compose | PostgreSQL gestionado | Región más cercana | Complejidad |
|---|---|---|---|---|---|---|
| **Railway** | PaaS de contenedores | USD 5/mes gratuito; sin tarjeta | Importa `docker-compose.yml` directamente | Plugin PostgreSQL incluido | us-west2 (Oregon) | **Muy baja** |
| Azure Container Apps | PaaS gestionado | **Azure for Students USD 100/año** sin tarjeta | Sí, via ACR + compose | Azure DB for PostgreSQL | Brazil South (~25 ms) | Media |
| AWS App Runner / ECS Fargate | PaaS / CaaS | AWS Educate (proceso variable) | Sí, via ECR + task definitions | Amazon RDS | us-east-1 | Media-Alta |
| Google Cloud Run | Serverless containers | USD 300 crédito inicial | Sí, via Artifact Registry | Cloud SQL | Southamerica-east1 | Media |
| Render | PaaS | Free tier (sleep tras 15 min inactividad) | Sí | Sí | Oregon | Baja |
| VM propia (DigitalOcean, Hetzner, etc.) | IaaS | ~USD 4–6/mes | `docker compose up` directo | Auto-gestionado | Variable | Baja-Media |
| Local / máquina académica | — | Sin costo | `docker compose up` | Auto-gestionado | — | **Mínima** |

### Decisión 2: Proveedor de despliegue

**Se usará Railway como plataforma de despliegue para los entornos de integración y demo** (cortes 2 y 3), con `docker compose` local como entorno de desarrollo y referencia (ADR-0011).

**Justificación:**
- Railway importa directamente el `docker-compose.yml` del repositorio, lo que preserva la reproducibilidad local como única fuente de verdad (ADR-0011). No hay configuración de infraestructura adicional.
- El free tier (USD 5/mes de crédito, sin tarjeta de crédito) es suficiente para los tres contenedores (api, worker, postgres) con la carga del proyecto académico.
- El plugin de PostgreSQL de Railway soporta extensión PostGIS (ADR-0005) mediante `docker-compose.yml` con imagen `postgis/postgis:16-3.4`.
- Despliegue por push a rama `main` integrado con GitHub Actions (ADR-0011): el pipeline corre las pruebas y, si pasan, Railway despliega automáticamente.
- La URL pública de Railway permite que el docente y los evaluadores accedan al sistema sin instalar nada.

**Alternativa recomendada si el equipo obtiene créditos de Azure for Students:**
Azure Container Apps en región **Brazil South** (latencia ~25 ms desde Armenia vs ~80 ms de us-east-1). La imagen Docker es idéntica; solo cambia el archivo de despliegue. Se registraría en este ADR como sustitución.

**Por qué no Render como primera opción:**
El sleep automático tras 15 minutos de inactividad en el free tier invalida las pruebas de carga (K-01) y la demo al docente. Railway no tiene esta limitación.

## Decisión

1. **Adoptamos el AWS Well-Architected Framework** como referencia para justificar y verificar las decisiones de arquitectura en nube. Sus 6 pilares se aplican al proyecto tal como se detalla en la tabla anterior.

2. **Desplegamos en Railway** (entorno de integración y demo) con `docker compose` como única fuente de verdad del despliegue local. La migración a Azure Container Apps (Brazil South) es el camino de evolución si se obtienen créditos de Azure for Students.

3. La imagen base del contenedor API y Worker es `php:8.3-fpm-alpine` + nginx alpine. La imagen de BD es `postgis/postgis:16-3.4`. Ninguna imagen depende de SDKs propietarios de AWS, Azure ni GCP, lo que garantiza portabilidad entre proveedores.

## Consecuencias

- **Positivas:**
  - El equipo tiene un marco de evaluación (AWS WAF) con documentación abundante para argumentar decisiones en la sustentación (RDA4 de AS II).
  - Railway permite demo funcional para el docente sin infraestructura propia ni tarjeta de crédito.
  - La portabilidad del stack (docker compose agnóstico) garantiza que cambiar de proveedor en corte 3 tenga costo cero de configuración.
  - El pilar de Optimización de Costos del AWS WAF guía explícitamente las decisiones de *right-sizing*, alineadas con R-04.

- **Negativas / riesgos:**
  - Railway puede tener indisponibilidad durante la demo si se supera el crédito gratuito mensual; mitigación: monitorear el consumo y tener `docker compose` local como respaldo inmediato.
  - AWS WAF como marco no garantiza que el equipo tenga acceso a las herramientas nativas de AWS (Trusted Advisor, Well-Architected Tool); se reemplaza con la documentación pública y la lista de verificación del pilar.
  - La región de Railway (us-west) no está en Latinoamérica; para producción real se evaluaría la migración a Azure Brazil South o GCP Southamerica-east1.

- **Cómo se verificará:**
  - `docker compose up` en máquina limpia levanta el sistema en < 5 minutos (verificación ADR-0011).
  - Pipeline GitHub Actions despliega a Railway en push a `main` tras pasar todas las puertas de calidad.
  - La tabla de aplicación del AWS WAF se revisa en la evaluación de arquitectura del corte 3 (Entregable 6 de AS II).
