<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/*
 * Seguridad transversal de la API (EC-03, EC-07; estándar de seguridad web del equipo).
 * C-18: cabeceras de seguridad en toda respuesta de /v1 y límite de peticiones por IP con
 * 429 + Retry-After en formato Problem Details (RFC 9457).
 */

describe('C-18 cabeceras de seguridad', function () {
    it('toda respuesta de la API lleva las cabeceras de seguridad', function (string $ruta) {
        $r = $this->getJson($ruta);

        expect($r->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($r->headers->get('X-Frame-Options'))->toBe('DENY')
            ->and($r->headers->get('Referrer-Policy'))->toBe('no-referrer')
            ->and($r->headers->get('Content-Security-Policy'))->toBe("default-src 'none'; frame-ancestors 'none'")
            ->and($r->headers->get('Permissions-Policy'))->toContain('geolocation=()')
            ->and($r->headers->get('Cross-Origin-Resource-Policy'))->toBe('cross-origin')
            ->and($r->headers->has('X-Powered-By'))->toBeFalse();
    })->with(['/v1/productos', '/v1/no-existe']);

    it('en HTTPS agrega HSTS', function () {
        $r = $this->getJson('https://localhost/v1/productos');

        expect($r->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
    });
});

describe('C-18 límite de peticiones por IP', function () {
    it('pasado el límite de lecturas responde 429 con Retry-After en Problem Details', function () {
        config(['seguridad.limite_lecturas_por_minuto' => 3]);

        foreach (range(1, 3) as $_) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.1'])->getJson('/v1/productos')->assertOk();
        }
        $r = $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.1'])->getJson('/v1/productos');

        $r->assertStatus(429)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 429);
        expect((int) $r->headers->get('Retry-After'))->toBeGreaterThan(0);
    });

    it('el límite es por IP: otra IP sigue siendo atendida', function () {
        config(['seguridad.limite_lecturas_por_minuto' => 1]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.2'])->getJson('/v1/productos')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.2'])->getJson('/v1/productos')->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.3'])->getJson('/v1/productos')->assertOk();
    });

    it('las escrituras tienen un límite propio, más estricto', function () {
        config(['seguridad.limite_escrituras_por_minuto' => 1]);
        $p = crearPresentacion(crearProducto());
        $e = crearEstablecimiento();

        $enviar = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.4'])
            ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/v1/reportes-precio', ['presentacionId' => $p, 'establecimientoId' => $e, 'precio' => '3000', 'observadoEn' => now()->subMinute()->toIso8601String()]);

        $enviar()->assertAccepted();
        $enviar()->assertStatus(429);
        // Las lecturas de la misma IP no se ven afectadas por el límite de escrituras.
        $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.4'])->getJson('/v1/productos')->assertOk();
    });

    it('los valores por defecto no bloquean un uso normal ni la prueba de humo (600 lecturas y 60 escrituras por minuto)', function () {
        expect(config('seguridad.limite_lecturas_por_minuto'))->toBe(600)
            ->and(config('seguridad.limite_escrituras_por_minuto'))->toBe(60);
    });
});
