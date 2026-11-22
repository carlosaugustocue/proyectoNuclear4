<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
 * Moderación — RF-20 (cola de casos y decisiones), RNF-11 (bitácora), EC-03.
 * C-17: contrato de GET /v1/moderacion/casos, POST /v1/moderacion/casos/{casoId}/decision
 * y GET /v1/moderacion/perfiles. La decisión entra por Confianza (mismo flujo de proyección):
 * Moderación nunca escribe el modelo de lectura de Comparación (AR-01).
 * ADR-0025: el moderador entra con su cuenta (rol MODERADOR) y la bitácora registra quién decidió.
 */

beforeEach(function () {
    $this->tokenModerador = tokenDeModerador();

    $this->arroz    = crearProducto(['nombre' => 'Arroz blanco']);
    $this->bolsa1kg = crearPresentacion($this->arroz, ['descripcion' => 'Bolsa 1 kg', 'cantidad' => '1', 'unidad' => 'KG']);
});

function comoModerador(): \Illuminate\Foundation\Testing\TestCase
{
    return test()->withHeaders(['Authorization' => 'Bearer ' . test()->tokenModerador]);
}

/** Cinco precios normales y uno atípico: devuelve la observación atípica (POR_VERIFICAR, con caso abierto). */
function observacionAtipica(string $presentacionId): string
{
    foreach (['2950', '3000', '3000', '3050', '3100'] as $i => $precio) {
        reportarPrecio($presentacionId, crearEstablecimiento(['nombre' => "Tienda {$i}"]), $precio, "10.0.1.{$i}");
    }

    return reportarPrecio($presentacionId, crearEstablecimiento(['nombre' => 'Tienda sospechosa']), '300', '10.0.9.9')
        ->json('observacionId');
}

function itemDe(string $presentacionId, string $nombre): ?array
{
    $items = test()->getJson('/v1/comparaciones?productoId=' . DB::table('presentaciones')->where('id', $presentacionId)->value('producto_id'))->json('items');

    return collect($items)->firstWhere('establecimiento.nombre', $nombre);
}

describe('C-17 acceso a la moderación', function () {
    $rutas = [
        ['GET', '/v1/moderacion/casos'],
        ['GET', '/v1/moderacion/perfiles'],
        ['POST', '/v1/moderacion/casos/0190a4c2-0000-7000-8000-000000000000/decision'],
    ];

    it('sin sesión responde 401', function (string $metodo, string $ruta) {
        $this->json($metodo, $ruta)->assertUnauthorized();
    })->with($rutas);

    it('una cuenta CIUDADANO no puede moderar: 403', function (string $metodo, string $ruta) {
        [, $token] = cuentaCiudadana();
        $this->withHeaders(['Authorization' => "Bearer {$token}"])->json($metodo, $ruta)->assertForbidden();
    })->with($rutas);

    it('el antiguo secreto compartido ya no da acceso', function () {
        config(['services.moderacion.token' => 'secreto-antiguo-compartido']);
        $this->withHeaders(['Authorization' => 'Bearer secreto-antiguo-compartido'])->getJson('/v1/moderacion/casos')->assertUnauthorized();
    });
});

describe('GET /v1/moderacion/casos (RF-20)', function () {
    it('lista los casos abiertos con lo necesario para decidir', function () {
        $obs = observacionAtipica($this->bolsa1kg);

        comoModerador()->getJson('/v1/moderacion/casos')
            ->assertOk()
            ->assertJsonPath('pagina.total', 1)
            ->assertJsonPath('items.0.origen', 'ANOMALIA_AUTOMATICA')
            ->assertJsonPath('items.0.estado', 'ABIERTO')
            ->assertJsonPath('items.0.observacionId', $obs)
            ->assertJsonPath('items.0.observacion.precio', '300.00')
            ->assertJsonPath('items.0.observacion.producto', 'Arroz blanco')
            ->assertJsonPath('items.0.observacion.establecimiento', 'Tienda sospechosa')
            ->assertJsonPath('items.0.observacion.estadoConfianza', 'POR_VERIFICAR')
            ->assertJsonPath('items.0.observacion.autorConCuenta', false);
    });

    it('no expone la identidad del reportante ni siquiera al moderador (EC-07)', function () {
        observacionAtipica($this->bolsa1kg);

        $claves = clavesJson(comoModerador()->getJson('/v1/moderacion/casos')->json());

        expect($claves)->not->toContain('reportadoPor')->not->toContain('ip')->not->toContain('reportado_por');
    });
});

describe('POST /v1/moderacion/casos/{casoId}/decision (RF-20, RNF-11)', function () {
    it('INVALIDAR retira el precio de la comparación y deja la decisión en bitácora', function () {
        $obs  = observacionAtipica($this->bolsa1kg);
        $caso = DB::table('casos_moderacion')->where('observacion_id', $obs)->value('id');

        comoModerador()->postJson("/v1/moderacion/casos/{$caso}/decision", [
            'accion' => 'INVALIDAR_OBSERVACION', 'justificacion' => 'Precio imposible frente al mercado',
        ])->assertOk()->assertJsonPath('estado', 'RESUELTO');

        $item = itemDe($this->bolsa1kg, 'Tienda sospechosa');
        expect($item === null || $item['vigente'] === false)->toBeTrue()
            ->and(DB::table('evaluaciones_confianza')->where('observacion_id', $obs)->value('estado'))->toBe('NO_CONFIABLE');
        $this->assertDatabaseHas('decisiones_moderacion', [
            'caso_id' => $caso, 'accion' => 'INVALIDAR_OBSERVACION', 'justificacion' => 'Precio imposible frente al mercado',
            'moderador' => DB::table('cuentas')->where('correo', 'moderador@ejemplo.test')->value('id'),
        ]);
        $this->assertDatabaseHas('casos_moderacion', ['id' => $caso, 'estado' => 'RESUELTO']);
    });

    it('CONFIRMAR deja el precio CONFIABLE y sin advertencia', function () {
        $obs  = observacionAtipica($this->bolsa1kg);
        $caso = DB::table('casos_moderacion')->where('observacion_id', $obs)->value('id');

        comoModerador()->postJson("/v1/moderacion/casos/{$caso}/decision", [
            'accion' => 'CONFIRMAR_OBSERVACION', 'justificacion' => 'Liquidación verificada en la tienda',
        ])->assertOk();

        $item = itemDe($this->bolsa1kg, 'Tienda sospechosa');
        expect($item['estadoConfianza'])->toBe('CONFIABLE')->and($item['advertencia'])->toBeNull();
    });

    it('un caso ya resuelto responde 409', function () {
        $caso = DB::table('casos_moderacion')->where('observacion_id', observacionAtipica($this->bolsa1kg))->value('id');
        $cuerpo = ['accion' => 'CERRAR_SIN_CAMBIOS', 'justificacion' => 'Revisado, sin acción necesaria'];

        comoModerador()->postJson("/v1/moderacion/casos/{$caso}/decision", $cuerpo)->assertOk();
        comoModerador()->postJson("/v1/moderacion/casos/{$caso}/decision", $cuerpo)->assertStatus(409);
    });

    it('valida la acción y exige una justificación de al menos 10 caracteres', function () {
        $caso = DB::table('casos_moderacion')->where('observacion_id', observacionAtipica($this->bolsa1kg))->value('id');

        comoModerador()->postJson("/v1/moderacion/casos/{$caso}/decision", ['accion' => 'BORRAR_TODO', 'justificacion' => 'corta'])
            ->assertStatus(422)->assertJsonValidationErrors(['accion', 'justificacion']);
    });

    it('un caso inexistente responde 404', function () {
        comoModerador()->postJson('/v1/moderacion/casos/0190a4c2-0000-7000-8000-000000000000/decision', [
            'accion' => 'CERRAR_SIN_CAMBIOS', 'justificacion' => 'No debería existir este caso',
        ])->assertNotFound();
    });

    it('SUSPENDER_CUENTA sobre un reporte anónimo responde 409: no hay cuenta que suspender', function () {
        $caso = DB::table('casos_moderacion')->where('observacion_id', observacionAtipica($this->bolsa1kg))->value('id');

        comoModerador()->postJson("/v1/moderacion/casos/{$caso}/decision", [
            'accion' => 'SUSPENDER_CUENTA', 'justificacion' => 'Patrón de reportes falsos',
        ])->assertStatus(409);
        $this->assertDatabaseHas('casos_moderacion', ['id' => $caso, 'estado' => 'ABIERTO']);
    });

    it('SUSPENDER_CUENTA suspende al autor, cierra sus sesiones e invalida el precio', function () {
        [$cuentaId, $token] = cuentaCiudadana();
        foreach (['2950', '3000', '3000', '3050', '3100'] as $i => $precio) {
            reportarPrecio($this->bolsa1kg, crearEstablecimiento(['nombre' => "Tienda {$i}"]), $precio, "10.0.1.{$i}");
        }
        $obs = $this->withHeaders(['Authorization' => "Bearer {$token}", 'Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])
            ->postJson('/v1/reportes-precio', [
                'presentacionId' => $this->bolsa1kg, 'establecimientoId' => crearEstablecimiento(['nombre' => 'Tienda sospechosa']),
                'precio' => '300', 'observadoEn' => now()->subMinute()->toIso8601String(),
            ])->assertAccepted()->json('observacionId');
        $caso = DB::table('casos_moderacion')->where('observacion_id', $obs)->value('id');
        // La consola sabe que el autor tiene cuenta (para ofrecer «suspender»), pero no quién es (EC-07).
        $listado = comoModerador()->getJson('/v1/moderacion/casos')->assertJsonPath('items.0.observacion.autorConCuenta', true);
        expect(json_encode($listado->json()))->not->toContain($cuentaId)->not->toContain('ciudadana@');

        comoModerador()->postJson("/v1/moderacion/casos/{$caso}/decision", [
            'accion' => 'SUSPENDER_CUENTA', 'justificacion' => 'Patrón de reportes falsos',
        ])->assertOk()->assertJsonPath('estado', 'RESUELTO');

        expect(DB::table('cuentas')->where('id', $cuentaId)->value('estado'))->toBe('SUSPENDIDA')
            ->and(DB::table('evaluaciones_confianza')->where('observacion_id', $obs)->value('estado'))->toBe('NO_CONFIABLE');
        $this->withHeaders(['Authorization' => "Bearer {$token}"])->getJson('/v1/cuentas/yo')->assertUnauthorized();
        $this->postJson('/v1/sesiones', ['correo' => 'ciudadana@ejemplo.test', 'contrasena' => 'frase-de-la-ciudadana-2026'])->assertUnauthorized();
    });

    it('una corrección de comercio solo admite CERRAR_SIN_CAMBIOS (no tiene observación)', function () {
        DB::table('casos_moderacion')->insert([
            'id' => $id = '0190a4c2-1111-7000-8000-000000000001', 'observacion_id' => '0190a4c2-2222-7000-8000-000000000002',
            'origen' => 'CORRECCION_COMERCIO', 'estado' => 'ABIERTO', 'detalle' => 'La dirección está mal', 'creado_en' => now(),
        ]);

        comoModerador()->postJson("/v1/moderacion/casos/{$id}/decision", ['accion' => 'INVALIDAR_OBSERVACION', 'justificacion' => 'No aplica a correcciones'])
            ->assertStatus(409);
        comoModerador()->postJson("/v1/moderacion/casos/{$id}/decision", ['accion' => 'CERRAR_SIN_CAMBIOS', 'justificacion' => 'Dirección corregida en el directorio'])
            ->assertOk();
    });
});

describe('GET /v1/moderacion/perfiles (RF-31)', function () {
    it('lista los perfiles pendientes con el id completo y sin los datos de contacto', function () {
        $est = crearEstablecimiento(['nombre' => 'Tienda Don Pepe']);
        $perfilId = $this->postJson('/v1/comercial/perfiles', [
            'establecimientoId' => $est, 'nombreContacto' => 'Contacto Privado', 'telefonoContacto' => '3001234567',
        ])->assertCreated()->json('perfilId');

        $respuesta = comoModerador()->getJson('/v1/moderacion/perfiles?estado=PENDIENTE')
            ->assertOk()
            ->assertJsonPath('items.0.perfilId', $perfilId)
            ->assertJsonPath('items.0.establecimiento.nombre', 'Tienda Don Pepe')
            ->assertJsonPath('items.0.estado', 'PENDIENTE');

        expect(json_encode($respuesta->json()))->not->toContain('3001234567')->not->toContain('Contacto Privado');
    });
});
