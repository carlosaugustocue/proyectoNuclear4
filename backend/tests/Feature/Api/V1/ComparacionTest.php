<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
 * GET /v1/comparaciones — RF-13 a RF-17, RN-01, RN-04 a RN-07, RN-09, RN-10.
 * Escenarios: EC-02 (actualidad), EC-03 (credibilidad), EC-06 (neutralidad),
 * EC-07 (sin datos personales). Flujo completo: reporte → confianza → proyección.
 */

beforeEach(function () {
    $this->producto = crearProducto(['nombre' => 'Arroz blanco Diana', 'tipo_vigencia' => 'ABARROTE']);
    $this->bolsa500 = crearPresentacion($this->producto, ['descripcion' => 'Bolsa 500 g', 'cantidad' => '500', 'unidad' => 'G']);
    $this->bolsa1kg = crearPresentacion($this->producto, ['descripcion' => 'Bolsa 1 kg', 'cantidad' => '1', 'unidad' => 'KG']);
    $this->tiendaA  = crearEstablecimiento(['nombre' => 'Tienda A', 'tipo' => 'TIENDA_BARRIO'], 4.5345, -75.6815);
    $this->tiendaB  = crearEstablecimiento(['nombre' => 'Supermercado B', 'tipo' => 'SUPERMERCADO'], 4.5600, -75.6600);
});

function comparar(array $params = [])
{
    return test()->getJson('/v1/comparaciones?' . http_build_query(array_merge(['productoId' => test()->producto], $params)));
}

describe('GET /v1/comparaciones — resultado y orden neutral', function () {

    it('normaliza por unidad base y ordena por precio por kilogramo ascendente (RN-01, RN-10)', function () {
        reportarPrecio($this->bolsa500, $this->tiendaA, '2990', '10.0.0.1'); // 5980/kg
        reportarPrecio($this->bolsa1kg, $this->tiendaB, '5500', '10.0.0.2'); // 5500/kg

        comparar()
            ->assertOk()
            ->assertJsonPath('producto.id', $this->producto)
            ->assertJsonPath('unidadBase', 'KG')
            ->assertJsonPath('criterioOrden.campo', 'precio_unidad')
            ->assertJsonPath('items.0.posicion', 1)
            ->assertJsonPath('items.0.establecimiento.nombre', 'Supermercado B')
            ->assertJsonPath('items.0.precioPorUnidad', '5500.00')
            ->assertJsonPath('items.0.presentacion.descripcion', 'Bolsa 1 kg')
            ->assertJsonPath('items.1.posicion', 2)
            ->assertJsonPath('items.1.precio', '2990.00')
            ->assertJsonPath('items.1.precioPorUnidad', '5980.00')
            ->assertJsonPath('contenidoPromocional', []);
    });

    it('declara siempre el criterio de orden y que nadie paga por su posición (EC-06, ADR-0009)', function () {
        reportarPrecio($this->bolsa500, $this->tiendaA, '2990');

        expect(comparar()->json('criterioOrden.descripcion'))
            ->toContain('precio por kilogramo')
            ->toContain('Ningún comercio paga por su posición');
    });

    it('ante un empate de precio por unidad gana la observación más reciente (RN-10)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '5000', '10.0.0.1', now()->subHours(5)->toIso8601String());
        reportarPrecio($this->bolsa1kg, $this->tiendaB, '5000', '10.0.0.2', now()->subHour()->toIso8601String());

        comparar()->assertJsonPath('items.0.establecimiento.nombre', 'Supermercado B');
    });

    it('ordena por distancia cuando se pide y se envía la ubicación de consulta', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '6000', '10.0.0.1'); // cerca, más caro
        reportarPrecio($this->bolsa1kg, $this->tiendaB, '5000', '10.0.0.2'); // lejos, más barato

        $res = comparar(['orden' => 'distancia', 'lat' => 4.5339, 'lon' => -75.6811, 'radioKm' => 10])
            ->assertOk()
            ->assertJsonPath('criterioOrden.campo', 'distancia')
            ->assertJsonPath('items.0.establecimiento.nombre', 'Tienda A');

        expect($res->json('items.0.establecimiento.distanciaKm'))->toBeLessThan($res->json('items.1.establecimiento.distanciaKm'));
    });

    it('filtra por presentación cuando se indica presentacionId', function () {
        reportarPrecio($this->bolsa500, $this->tiendaA, '2990', '10.0.0.1');
        reportarPrecio($this->bolsa1kg, $this->tiendaB, '5500', '10.0.0.2');

        comparar(['presentacionId' => $this->bolsa500])
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.presentacion.id', $this->bolsa500);
    });

    it('responde con Cache-Control público de 30 s (modelo de lectura, EC-04)', function () {
        $cache = comparar()->assertOk()->headers->get('Cache-Control');

        expect($cache)->toContain('public')->toContain('max-age=30');
    });
})->group('feature');

describe('GET /v1/comparaciones — confianza de los datos', function () {

    it('un reporte aislado se muestra como vigente, sin corroborar y sin advertencia', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '5000');

        comparar()
            ->assertJsonPath('items.0.estadoConfianza', 'NO_CORROBORADO')
            ->assertJsonPath('items.0.vigente', true)
            ->assertJsonPath('items.0.confirmaciones', 0)
            ->assertJsonPath('items.0.fuente', 'REPORTANTE')
            ->assertJsonPath('items.0.advertencia', null);
    });

    it('dos reportantes independientes con diferencia ≤ 5 % en 48 h → CONFIABLE (RN-05)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3000', '10.0.0.1');
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3100', '10.0.0.2');

        comparar()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.precio', '3100.00')
            ->assertJsonPath('items.0.estadoConfianza', 'CONFIABLE')
            ->assertJsonPath('items.0.confirmaciones', 1);
    });

    it('el mismo reportante no se corrobora a sí mismo (RN-05)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3000', '10.0.0.1', now()->subHours(30)->toIso8601String());
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3050', '10.0.0.1');

        comparar()->assertJsonPath('items.0.estadoConfianza', 'NO_CORROBORADO');
    });

    it('un precio atípico queda POR_VERIFICAR, se muestra con advertencia y abre un caso de moderación (RN-06, EC-03)', function () {
        foreach (['2950', '3000', '3000', '3050', '3100'] as $i => $precio) {
            $tienda = crearEstablecimiento(['nombre' => "Tienda {$i}"]);
            reportarPrecio($this->bolsa1kg, $tienda, $precio, "10.0.1.{$i}");
        }
        $sospechosa = crearEstablecimiento(['nombre' => 'Tienda sospechosa']);
        $obs = reportarPrecio($this->bolsa1kg, $sospechosa, '300', '10.0.9.9')->json('observacionId');

        $item = collect(comparar()->json('items'))->firstWhere('establecimiento.nombre', 'Tienda sospechosa');

        expect($item['estadoConfianza'])->toBe('POR_VERIFICAR')
            ->and($item['vigente'])->toBeTrue()
            ->and($item['advertencia'])->toContain('verificación');

        $this->assertDatabaseHas('casos_moderacion', [
            'observacion_id' => $obs,
            'origen'         => 'ANOMALIA_AUTOMATICA',
            'estado'         => 'ABIERTO',
        ]);
    });

    it('una cuenta nueva no reemplaza por sí sola un precio vigente corroborado (RN-07)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3000', '10.0.0.1');
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3050', '10.0.0.2'); // corroborado
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '2700', '10.0.0.3'); // -11 %, sin corroborar

        comparar()
            ->assertJsonPath('items.0.precio', '3050.00')
            ->assertJsonPath('items.0.estadoConfianza', 'CONFIABLE');
    });

    it('el precio nuevo reemplaza al anterior en la comparación (actualidad, EC-02)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3000', '10.0.0.1');
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3090', '10.0.0.2');

        $precios = collect(comparar()->json('items'))->pluck('precio')->all();

        expect($precios)->toBe(['3090.00']);
    });
})->group('feature');

describe('GET /v1/comparaciones — vigencia (RN-04, RN-09)', function () {

    it('un abarrote reportado hace 9 días se muestra con la advertencia de desactualizado', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '5000', '10.0.0.1', now()->subDays(9)->toIso8601String());

        comparar()
            ->assertJsonPath('items.0.vigente', true)
            ->assertJsonPath('items.0.advertencia', 'Precio reportado hace 9 días; puede estar desactualizado');
    });

    it('un precio con más del doble de la vigencia no se muestra como vigente por defecto', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '5000', '10.0.0.1', now()->subDays(20)->toIso8601String());

        comparar()->assertOk()->assertJsonCount(0, 'items');
    });

    it('con incluirNoVigentes=true lo incluye marcado como no vigente y con advertencia (RF-16)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '5000', '10.0.0.1', now()->subDays(20)->toIso8601String());

        comparar(['incluirNoVigentes' => 'true'])
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.vigente', false)
            ->assertJsonPath('items.0.advertencia', 'Precio histórico de hace 20 días; ya no se considera vigente');
    });

    it('los perecederos vencen antes (3 días)', function () {
        $fruta = crearProducto(['nombre' => 'Tomate chonto', 'tipo_vigencia' => 'PERECEDERO', 'categoria' => 'Frutas y verduras']);
        $kilo  = crearPresentacion($fruta, ['descripcion' => 'Por kilo', 'cantidad' => '1', 'unidad' => 'KG']);
        reportarPrecio($kilo, $this->tiendaA, '4000', '10.0.0.1', now()->subDays(5)->toIso8601String());

        $this->getJson("/v1/comparaciones?productoId={$fruta}")
            ->assertJsonPath('items.0.advertencia', 'Precio reportado hace 5 días; puede estar desactualizado');
    });
})->group('feature');

describe('GET /v1/comparaciones — contrato, privacidad y errores', function () {

    it('no expone datos personales de reportantes (EC-07, prueba de contrato C-07)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3000', '10.0.0.1');
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3050', '10.0.0.2');

        $res    = comparar()->assertOk();
        $claves = clavesJson($res->json());

        expect($claves)->not->toContain('reportadoPor')
            ->not->toContain('reportado_por')
            ->not->toContain('ip')
            ->not->toContain('claveIdempotencia')
            ->and($res->getContent())->not->toContain('anon-');
        // ADR-0026: el id de la observación se expone para confirmarla o marcarla (RF-10, RF-19). Es un
        // UUID opaco sin relación con quien reportó: ningún endpoint público lo asocia a un reportante.
        expect($res->json('items.0.observacionId'))->toMatch('/^[0-9a-f-]{36}$/')
            ->and(DB::table('registros_precio')->where('id', $res->json('items.0.observacionId'))->value('reportado_por'))
            ->not->toBeIn(array_map('strval', \Illuminate\Support\Arr::flatten($res->json())));
    });

    it('cada item solo tiene los campos del contrato: ningún atributo comercial (C-06, AR-02)', function () {
        reportarPrecio($this->bolsa1kg, $this->tiendaA, '3000');

        $permitidos = ['posicion', 'observacionId', 'establecimiento', 'presentacion', 'precio', 'precioPorUnidad', 'observadoEn',
            'fuente', 'confirmaciones', 'estadoConfianza', 'vigente', 'advertencia'];

        expect(array_diff(array_keys(comparar()->json('items.0')), $permitidos))->toBe([])
            // id opaco de la observación, para confirmarla o marcarla (RF-10, RF-19); no identifica a nadie
            ->and(comparar()->json('items.0.observacionId'))->toBeString();
    });

    it('responde 404 en Problem Details si el producto no existe', function () {
        $this->getJson('/v1/comparaciones?productoId=0193a1d2-0000-7000-8000-000000000000')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json');
    });

    it('responde 400 sin productoId o con orden=distancia sin ubicación', function () {
        $this->getJson('/v1/comparaciones')->assertStatus(400);
        comparar(['orden' => 'distancia'])->assertStatus(400);
        comparar(['orden' => 'patrocinado'])->assertStatus(400);
    });
})->group('feature');

describe('GET /v1/comparaciones/resumen', function () {
    it('resume el menor precio vigente y cuántos establecimientos lo reportan por producto', function () {
        reportarPrecio($this->bolsa500, $this->tiendaA, '2990', '10.0.0.1');
        reportarPrecio($this->bolsa1kg, $this->tiendaB, '5500', '10.0.0.2');
        $sinPrecios = crearProducto(['nombre' => 'Lentejas']);

        $this->getJson("/v1/comparaciones/resumen?productoIds={$this->producto},{$sinPrecios}")
            ->assertOk()
            ->assertJsonPath('items.0.productoId', $this->producto)
            ->assertJsonPath('items.0.unidadBase', 'KG')
            ->assertJsonPath('items.0.precioPorUnidadMin', '5500.00')
            ->assertJsonPath('items.0.establecimientos', 2)
            ->assertJsonPath('items.1.productoId', $sinPrecios)
            ->assertJsonPath('items.1.establecimientos', 0)
            ->assertJsonPath('items.1.precioPorUnidadMin', null);
    });
})->group('feature');
