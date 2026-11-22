<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Catalogo\Domain\Repositories\CatalogoLectura;
use App\Domains\Catalogo\Infrastructure\Persistence\PgCatalogoLectura;
use App\Domains\Comparacion\Domain\Repositories\ModeloLecturaComparacion;
use App\Domains\Comparacion\Infrastructure\Listeners\ProyectarComparacionAlEvaluarConfianza;
use App\Domains\Comparacion\Infrastructure\Persistence\PgModeloLecturaComparacion;
use App\Domains\Confianza\Domain\Events\ConfianzaEvaluada;
use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Confianza\Infrastructure\Listeners\EvaluarConfianzaAlRegistrarPrecio;
use App\Domains\Confianza\Infrastructure\Persistence\PgEvaluacionesRepositorio;
use App\Domains\Establecimientos\Domain\Repositories\DirectorioLectura;
use App\Domains\Establecimientos\Infrastructure\Persistence\PgDirectorioLectura;
use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use App\Domains\Moderacion\Infrastructure\Listeners\AbrirCasoAlEvaluarConfianza;
use App\Domains\Moderacion\Infrastructure\Persistence\PgCasosModeracion;
use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use App\Domains\Precios\Domain\Repositories\RegistroPrecioRepository;
use App\Domains\Precios\Domain\Repositories\ResolutorPresentaciones;
use App\Domains\Precios\Infrastructure\Catalogo\ResolutorPresentacionesCatalogo;
use App\Domains\Precios\Infrastructure\Persistence\EloquentRegistroPrecioRepository;
use App\Domains\Precios\Infrastructure\Persistence\PgObservacionesLectura;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Puertos de dominio → adaptadores de infraestructura, por contexto delimitado. */
    public function register(): void
    {
        // Catálogo y Establecimientos
        $this->app->bind(CatalogoLectura::class, PgCatalogoLectura::class);
        // Comercial (ADR-0023): implementa el puerto de Comparación; sin este enlace, contenidoPromocional viaja vacío.
        $this->app->bind(\App\Domains\Comercial\Domain\Repositories\PerfilesComerciales::class, \App\Domains\Comercial\Infrastructure\Persistence\PgPerfilesComerciales::class);
        $this->app->bind(\App\Domains\Comercial\Domain\Repositories\Promociones::class, \App\Domains\Comercial\Infrastructure\Persistence\PgPromociones::class);
        $this->app->bind(\App\Domains\Comparacion\Application\PromocionesVigentes::class, \App\Domains\Comercial\Infrastructure\PromocionesVigentesComercial::class);
        $this->app->bind(\App\Domains\Canasta\Domain\Ports\FuentePreciosVigentes::class, \App\Domains\Canasta\Infrastructure\FuentePreciosVigentesComparacion::class);
        $this->app->bind(DirectorioLectura::class, PgDirectorioLectura::class);

        // Precios
        $this->app->bind(RegistroPrecioRepository::class, EloquentRegistroPrecioRepository::class);
        $this->app->bind(ResolutorPresentaciones::class, ResolutorPresentacionesCatalogo::class);
        $this->app->bind(ObservacionesLectura::class, PgObservacionesLectura::class);

        // Confianza, Comparación y Moderación
        $this->app->bind(EvaluacionesRepositorio::class, PgEvaluacionesRepositorio::class);
        $this->app->bind(ModeloLecturaComparacion::class, PgModeloLecturaComparacion::class);
        $this->app->bind(CasosModeracion::class, PgCasosModeracion::class);
        $this->app->bind(\App\Domains\Moderacion\Domain\Repositories\MarcasObservacion::class, \App\Domains\Moderacion\Infrastructure\Persistence\PgMarcasObservacion::class);
        $this->app->bind(\App\Domains\Moderacion\Domain\Ports\SuspensionDeCuentas::class, \App\Domains\Identidad\Infrastructure\SuspensionDeCuentasIdentidad::class);
        // Identidad (ADR-0025)
        $this->app->bind(\App\Domains\Identidad\Domain\Repositories\Cuentas::class, \App\Domains\Identidad\Infrastructure\Persistence\PgCuentas::class);
        $this->app->bind(\App\Domains\Identidad\Domain\Repositories\Sesiones::class, \App\Domains\Identidad\Infrastructure\Persistence\PgSesiones::class);
    }

    /**
     * Flujo de eventos de dominio por el outbox (ADR-0003), todos en la cola domain-events:
     *   PrecioRegistrado  → Confianza evalúa (RN-05, RN-06, RN-07)
     *   ConfianzaEvaluada → Comparación proyecta el modelo de lectura (RN-09, EC-02)
     *                     → Moderación abre caso si queda POR_VERIFICAR (EC-03)
     */
    public function boot(): void
    {
        Event::listen(PrecioRegistrado::class, EvaluarConfianzaAlRegistrarPrecio::class);
        Event::listen(ConfianzaEvaluada::class, ProyectarComparacionAlEvaluarConfianza::class);
        Event::listen(ConfianzaEvaluada::class, AbrirCasoAlEvaluarConfianza::class);

        $this->configurarLimitesDePeticiones();
    }

    /**
     * C-18: cupos por IP del cliente, separados para lecturas y escrituras (una IP que agota sus
     * escrituras puede seguir consultando). Comparar una canasta es una consulta aunque use POST.
     */
    private function configurarLimitesDePeticiones(): void
    {
        // ADR-0025: fuerza bruta contra contraseñas (por IP y correo) y creación masiva de cuentas (por IP).
        RateLimiter::for('inicio-sesion', static fn (Request $r) => Limit::perMinute(5)
            ->by('sesion|' . $r->ip() . '|' . mb_strtolower(trim((string) $r->input('correo')))));
        RateLimiter::for('registro-cuentas', static fn (Request $r) => Limit::perHour(10)->by('registro|' . $r->ip()));

        RateLimiter::for('api-por-ip', static function (Request $request): Limit {
            $esLectura = $request->isMethodSafe() || $request->is('v1/canastas/comparacion');

            return $esLectura
                ? Limit::perMinute((int) config('seguridad.limite_lecturas_por_minuto'))->by('lectura|' . $request->ip())
                : Limit::perMinute((int) config('seguridad.limite_escrituras_por_minuto'))->by('escritura|' . $request->ip());
        });
    }
}
