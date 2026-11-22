<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Módulo Comercial (corte 3) — RF-31 a RF-34, RN-14 a RN-16, ADR-0015, ADR-0022, ADR-0023.
 * C-16: contrato de /v1/comercial/* y /v1/moderacion/perfiles/*, autorización y privacidad (EC-07).
 * C-06 (ampliada): una promoción va solo en contenidoPromocional; no cambia items, orden ni total (AR-02).
 * I-08: el precio oficial pasa por las mismas reglas de confianza que un reporte (ADR-0015).
 */

beforeEach(function () {
    $this->tokenModerador = tokenDeModerador(); // ADR-0025: el moderador entra con su cuenta

    $this->arroz    = crearProducto(['nombre' => 'Arroz blanco', 'tipo_vigencia' => 'ABARROTE']);
    $this->arroz1kg = crearPresentacion($this->arroz, ['descripcion' => 'Bolsa 1 kg', 'cantidad' => '1', 'unidad' => 'KG']);
    $this->tiendaA  = crearEstablecimiento(['nombre' => 'Tienda A', 'tipo' => 'TIENDA_BARRIO']);
    $this->tiendaB  = crearEstablecimiento(['nombre' => 'Supermercado B', 'tipo' => 'SUPERMERCADO']);
});

function solicitarPerfil(string $establecimientoId)
{
    return test()->postJson('/v1/comercial/perfiles', [
        'establecimientoId' => $establecimientoId,
        'nombreContacto'    => 'Contacto Privado',
        'telefonoContacto'  => '3001234567',
    ]);
}

/** $token: '' = sesión del moderador de la prueba; null = sin credencial; otro valor = credencial inventada. */
function moderar(string $perfilId, string $accion, ?string $token = '')
{
    $token     = $token === '' ? test()->tokenModerador : $token;
    $cabeceras = $token === null ? [] : ['Authorization' => "Bearer {$token}"];

    return test()->withHeaders($cabeceras)->postJson("/v1/moderacion/perfiles/{$perfilId}/{$accion}");
}

/** @return array{perfilId: string, credencial: string} */
function comercioVerificado(string $establecimientoId): array
{
    $perfilId = solicitarPerfil($establecimientoId)->assertCreated()->json('perfilId');
    $credencial = moderar($perfilId, 'verificacion')->assertOk()->json('credencial');

    return ['perfilId' => $perfilId, 'credencial' => $credencial];
}

function comoComercio(?string $credencial)
{
    return test()->withHeaders($credencial === null ? [] : ['Authorization' => "Bearer {$credencial}"]);
}

function publicarPrecioOficial(string $credencial, string $presentacionId, string $establecimientoId, string $precio)
{
    return comoComercio($credencial)
        ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/v1/comercial/precios-oficiales', [
            'presentacionId' => $presentacionId, 'establecimientoId' => $establecimientoId, 'precio' => $precio,
        ]);
}

function publicarPromocion(string $credencial, string $establecimientoId, string $productoId, array $extra = [])
{
    return comoComercio($credencial)->postJson('/v1/comercial/promociones', [
        'establecimientoId' => $establecimientoId,
        'productoId'        => $productoId,
        'tipo'              => 'DESCUENTO_PORCENTUAL',
        'valor'             => '20',
        'inicio'            => now()->subHour()->toIso8601String(),
        'fin'               => now()->addDays(7)->toIso8601String(),
        ...$extra,
    ]);
}

function estadoConfianza(string $observacionId): ?string
{
    return DB::table('evaluaciones_confianza')->where('observacion_id', $observacionId)->value('estado');
}

describe('C-16 perfil comercial y credencial (RF-31, ADR-0022)', function () {

    it('solicita un perfil PENDIENTE sin devolver los datos de contacto', function () {
        $r = solicitarPerfil($this->tiendaA)->assertCreated()->assertJsonPath('estado', 'PENDIENTE');

        expect($r->json('perfilId'))->toBeString()
            ->and($r->getContent())->not->toContain('Contacto Privado')->not->toContain('3001234567');
    });

    it('rechaza un perfil para un establecimiento inexistente', function () {
        solicitarPerfil((string) Str::uuid())->assertStatus(422)->assertHeader('Content-Type', 'application/problem+json');
    });

    it('solo un moderador con credencial puede verificar', function (?string $token) {
        $perfilId = solicitarPerfil($this->tiendaA)->json('perfilId');
        moderar($perfilId, 'verificacion', $token)->assertUnauthorized()->assertHeader('Content-Type', 'application/problem+json');
    })->with(['sin token' => [null], 'token equivocado' => ['otro-token']]);

    it('al verificar entrega la credencial una sola vez y guarda solo su hash', function () {
        $perfilId = solicitarPerfil($this->tiendaA)->json('perfilId');
        $credencial = moderar($perfilId, 'verificacion')->assertOk()
            ->assertJsonPath('estado', 'VERIFICADO')->json('credencial');

        $fila = (array) DB::table('perfiles_comerciales')->where('id', $perfilId)->first();
        expect($credencial)->toHaveLength(40)
            ->and($fila['credencial_hash'])->toBe(hash('sha256', $credencial))
            ->and(json_encode($fila))->not->toContain($credencial);

        moderar($perfilId, 'verificacion')->assertStatus(422); // ya no está pendiente
    });
});

describe('C-16 autorización de las publicaciones (RN-15)', function () {

    it('rechaza publicar sin credencial o con una credencial falsa', function (?string $credencial) {
        comoComercio($credencial)->postJson('/v1/comercial/promociones', [])
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/problem+json');
    })->with(['sin credencial' => [null], 'credencial falsa' => [str_repeat('a', 40)]]);

    it('rechaza publicar sobre otro establecimiento con 403', function () {
        $comercio = comercioVerificado($this->tiendaA);

        publicarPromocion($comercio['credencial'], $this->tiendaB, $this->arroz)
            ->assertForbidden()
            ->assertJsonPath('detail', 'El perfil solo puede publicar sobre su propio establecimiento.');
    });

    it('un perfil revocado deja de publicar', function () {
        $comercio = comercioVerificado($this->tiendaA);
        moderar($comercio['perfilId'], 'revocacion')->assertOk()->assertJsonPath('estado', 'REVOCADO');

        publicarPromocion($comercio['credencial'], $this->tiendaA, $this->arroz)
            ->assertForbidden()
            ->assertJsonPath('detail', 'El perfil comercial fue revocado.');
    });

    it('devuelve 422 con todos los errores de una promoción inválida (RN-14)', function () {
        $comercio = comercioVerificado($this->tiendaA);

        publicarPromocion($comercio['credencial'], $this->tiendaA, $this->arroz, [
            'valor' => '95', 'fin' => now()->subDay()->toIso8601String(),
        ])->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonCount(2, 'errors.promocion');
    });

    it('la corrección abre un caso de moderación (RF-34)', function () {
        $comercio = comercioVerificado($this->tiendaA);

        comoComercio($comercio['credencial'])->postJson('/v1/comercial/correcciones', [
            'establecimientoId' => $this->tiendaA,
            'detalle'           => 'La dirección del directorio está desactualizada.',
        ])->assertAccepted()->assertJsonPath('estado', 'EN_MODERACION');

        expect(DB::table('casos_moderacion')->where('origen', 'CORRECCION_COMERCIO')->count())->toBe(1);
    });
});

describe('C-06 ampliada: las promociones no alteran la comparación (RF-33, RN-16, AR-02)', function () {

    beforeEach(function () {
        reportarPrecio($this->arroz1kg, $this->tiendaA, '4000.00', '10.0.0.1')->assertAccepted();
        reportarPrecio($this->arroz1kg, $this->tiendaB, '3500.00', '10.0.0.2')->assertAccepted();
        $this->comercio = comercioVerificado($this->tiendaA);
    });

    it('la promoción aparece solo en contenidoPromocional y items no cambia', function () {
        $antes = $this->getJson("/v1/comparaciones?productoId={$this->arroz}")->assertOk()->json('items');

        publicarPromocion($this->comercio['credencial'], $this->tiendaA, $this->arroz)
            ->assertCreated()->assertJsonPath('descripcion', '20 % de descuento');

        $despues = $this->getJson("/v1/comparaciones?productoId={$this->arroz}")->assertOk();
        $despues->assertJsonPath('contenidoPromocional.0.esPromocion', true)
            ->assertJsonPath('contenidoPromocional.0.descripcion', '20 % de descuento')
            ->assertJsonPath('contenidoPromocional.0.establecimiento.nombre', 'Tienda A')
            ->assertJsonPath('contenidoPromocional.0.productoId', $this->arroz);

        $ids = fn (array $items): array => array_map(fn (array $i): string => $i['establecimiento']['id'], $items);
        expect($ids($despues->json('items')))->toBe($ids($antes))
            ->and($despues->json('items.0.establecimiento.nombre'))->toBe('Supermercado B'); // sigue primero el más barato
    });

    it('en la canasta la promoción se muestra aparte y no cambia el total', function () {
        publicarPromocion($this->comercio['credencial'], $this->tiendaA, $this->arroz)->assertCreated();

        $this->postJson('/v1/canastas/comparacion', ['orden' => 'total', 'items' => [
            ['productoId' => $this->arroz, 'cantidad' => '1', 'unidad' => 'kg'],
        ]])->assertOk()
            ->assertJsonPath('establecimientos.1.establecimiento.nombre', 'Tienda A')
            ->assertJsonPath('establecimientos.1.total', '4000.00')
            ->assertJsonPath('contenidoPromocional.0.descripcion', '20 % de descuento');
    });

    it('una promoción retirada deja de mostrarse', function () {
        $id = publicarPromocion($this->comercio['credencial'], $this->tiendaA, $this->arroz)->json('promocionId');

        comoComercio($this->comercio['credencial'])->deleteJson("/v1/comercial/promociones/{$id}")->assertNoContent();

        $this->getJson("/v1/comparaciones?productoId={$this->arroz}")->assertJsonPath('contenidoPromocional', []);
    });

    it('al revocar el perfil sus promociones dejan de mostrarse', function () {
        publicarPromocion($this->comercio['credencial'], $this->tiendaA, $this->arroz)->assertCreated();
        moderar($this->comercio['perfilId'], 'revocacion')->assertOk();

        $this->getJson("/v1/comparaciones?productoId={$this->arroz}")->assertJsonPath('contenidoPromocional', []);
    });

    it('ninguna respuesta pública expone el contacto ni la credencial (EC-07)', function () {
        publicarPromocion($this->comercio['credencial'], $this->tiendaA, $this->arroz)->assertCreated();

        $cuerpo = $this->getJson("/v1/comparaciones?productoId={$this->arroz}")->getContent();
        expect($cuerpo)->not->toContain('Contacto Privado')
            ->not->toContain('3001234567')
            ->not->toContain($this->comercio['credencial'])
            ->not->toContain('perfil');
    });
});

describe('I-08 el precio oficial pasa por las reglas de confianza (RF-32, ADR-0015)', function () {

    beforeEach(function () {
        $this->comercio = comercioVerificado($this->tiendaA);
    });

    it('se registra con fuente COMERCIO y se muestra en la comparación', function () {
        $id = publicarPrecioOficial($this->comercio['credencial'], $this->arroz1kg, $this->tiendaA, '4000.00')
            ->assertAccepted()->json('observacionId');

        expect(DB::table('registros_precio')->where('id', $id)->value('fuente'))->toBe('COMERCIO');
        $this->getJson("/v1/comparaciones?productoId={$this->arroz}")
            ->assertJsonPath('items.0.fuente', 'COMERCIO');
    });

    it('corroborado por un reporte independiente a ≤ 5 % queda CONFIABLE', function () {
        reportarPrecio($this->arroz1kg, $this->tiendaA, '4000.00', '10.0.0.7')->assertAccepted();
        $id = publicarPrecioOficial($this->comercio['credencial'], $this->arroz1kg, $this->tiendaA, '4100.00')->json('observacionId');

        expect(estadoConfianza($id))->toBe('CONFIABLE');
    });

    it('sin corroboración no es confiable por ser oficial', function () {
        reportarPrecio($this->arroz1kg, $this->tiendaA, '4000.00', '10.0.0.7')->assertAccepted();
        $id = publicarPrecioOficial($this->comercio['credencial'], $this->arroz1kg, $this->tiendaA, '4400.00')->json('observacionId');

        expect(estadoConfianza($id))->toBe('NO_CORROBORADO');
    });

    it('un precio oficial anómalo queda POR_VERIFICAR y abre un caso', function () {
        reportarPrecio($this->arroz1kg, $this->tiendaA, '4000.00', '10.0.0.7')->assertAccepted();
        reportarPrecio($this->arroz1kg, $this->tiendaA, '4050.00', '10.0.0.8')->assertAccepted();
        $id = publicarPrecioOficial($this->comercio['credencial'], $this->arroz1kg, $this->tiendaA, '1000.00')->json('observacionId');

        expect(estadoConfianza($id))->toBe('POR_VERIFICAR')
            ->and(DB::table('casos_moderacion')->where('observacion_id', $id)->value('origen'))->toBe('ANOMALIA_AUTOMATICA');
    });
});
