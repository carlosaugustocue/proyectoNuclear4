<?php

declare(strict_types=1);

/*
 * POST /v1/canastas/comparacion — RF-27 a RF-30, RN-11 a RN-13 (Anexo 1, A1).
 * C-14: contrato y reglas de la canasta de extremo a extremo (reporte → confianza → proyección → canasta).
 * C-15: la respuesta no expone datos personales (EC-07, ADR-0018).
 */

beforeEach(function () {
    $this->arroz  = crearProducto(['nombre' => 'Arroz blanco', 'tipo_vigencia' => 'ABARROTE']);
    $this->leche  = crearProducto(['nombre' => 'Leche entera', 'tipo_vigencia' => 'ABARROTE']);
    $this->arroz1kg = crearPresentacion($this->arroz, ['descripcion' => 'Bolsa 1 kg', 'cantidad' => '1', 'unidad' => 'KG']);
    $this->leche1l  = crearPresentacion($this->leche, ['descripcion' => 'Bolsa 1 L', 'cantidad' => '1', 'unidad' => 'L']);
    $this->tiendaA  = crearEstablecimiento(['nombre' => 'Tienda A', 'tipo' => 'TIENDA_BARRIO']);
    $this->tiendaB  = crearEstablecimiento(['nombre' => 'Supermercado B', 'tipo' => 'SUPERMERCADO']);

    reportarPrecio($this->arroz1kg, $this->tiendaA, '4000.00', '10.0.0.1')->assertAccepted();
    reportarPrecio($this->leche1l, $this->tiendaA, '3000.00', '10.0.0.2')->assertAccepted();
    reportarPrecio($this->arroz1kg, $this->tiendaB, '3500.00', '10.0.0.3')->assertAccepted();
});

function canasta(array $cuerpo)
{
    return test()->postJson('/v1/canastas/comparacion', $cuerpo);
}

describe('C-14 POST /v1/canastas/comparacion', function () {

    it('calcula el total por establecimiento con cantidades normalizadas y ordena por disponibilidad', function () {
        $r = canasta(['items' => [
            ['productoId' => $this->arroz, 'cantidad' => '500', 'unidad' => 'g'],
            ['productoId' => $this->leche, 'cantidad' => '2', 'unidad' => 'L'],
        ]])->assertOk();

        $r->assertJsonPath('criterioOrden.campo', 'disponibilidad')
            ->assertJsonPath('totalProductos', 2)
            ->assertJsonPath('establecimientos.0.establecimiento.nombre', 'Tienda A')
            ->assertJsonPath('establecimientos.0.total', '8000.00')       // 0,5 × 4000 + 2 × 3000
            ->assertJsonPath('establecimientos.0.completa', true)
            ->assertJsonPath('contenidoPromocional', []);
    });

    it('señala el faltante sin sumarlo como cero', function () {
        $r = canasta(['items' => [
            ['productoId' => $this->arroz, 'cantidad' => '1', 'unidad' => 'kg'],
            ['productoId' => $this->leche, 'cantidad' => '1', 'unidad' => 'L'],
        ]])->assertOk();

        $r->assertJsonPath('establecimientos.1.establecimiento.nombre', 'Supermercado B')
            ->assertJsonPath('establecimientos.1.total', '3500.00')
            ->assertJsonPath('establecimientos.1.faltantes', [$this->leche])
            ->assertJsonPath('establecimientos.1.disponibles', 1)
            ->assertJsonPath('establecimientos.1.completa', false);
    });

    it('con orden=total pone primero el menor total y lo declara', function () {
        canasta(['orden' => 'total', 'items' => [
            ['productoId' => $this->arroz, 'cantidad' => '1', 'unidad' => 'kg'],
            ['productoId' => $this->leche, 'cantidad' => '1', 'unidad' => 'L'],
        ]])->assertOk()
            ->assertJsonPath('criterioOrden.campo', 'total')
            ->assertJsonPath('establecimientos.0.establecimiento.nombre', 'Supermercado B');
    });

    it('responde 422 en Problem Details ante una canasta inválida', function (array $items) {
        canasta(['items' => $items])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json');
    })->with([
        'vacía'            => [[]],
        'más de 20'        => [array_fill(0, 21, ['productoId' => '00000000-0000-0000-0000-000000000000', 'cantidad' => '1', 'unidad' => 'kg'])],
        'cantidad cero'    => fn () => [[['productoId' => test()->arroz, 'cantidad' => '0', 'unidad' => 'kg']]],
        'unidad no comparable' => fn () => [[['productoId' => test()->arroz, 'cantidad' => '1', 'unidad' => 'L']]],
        'repetido'         => fn () => [[['productoId' => test()->arroz, 'cantidad' => '1', 'unidad' => 'kg'], ['productoId' => test()->arroz, 'cantidad' => '2', 'unidad' => 'kg']]],
    ]);

    it('rechaza un criterio de orden no declarado', function () {
        canasta(['orden' => 'patrocinado', 'items' => [['productoId' => $this->arroz, 'cantidad' => '1', 'unidad' => 'kg']]])
            ->assertStatus(422);
    });
});

describe('C-15 privacidad de la canasta', function () {

    it('no expone datos personales ni guarda la canasta', function () {
        $tablas = collect(\Illuminate\Support\Facades\DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"))->pluck('tablename');
        $r = canasta(['items' => [['productoId' => $this->arroz, 'cantidad' => '1', 'unidad' => 'kg']]])->assertOk();

        $claves = clavesJson($r->json());
        foreach (['reportante', 'reportanteId', 'seudonimo', 'ip', 'ubicacionDispositivo', 'email'] as $prohibida) {
            expect($claves)->not->toContain($prohibida);
        }
        expect($tablas->filter(fn (string $t): bool => str_contains($t, 'canasta')))->toBeEmpty();
    });
});
