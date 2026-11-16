<?php

declare(strict_types=1);

/*
 * RF-05 / RF-06 — Directorio de establecimientos y búsqueda por cercanía.
 * La ubicación es la del ESTABLECIMIENTO (dato público), nunca la del dispositivo.
 */

describe('GET /v1/establecimientos', function () {
    it('lista el directorio con su ubicación pública', function () {
        crearEstablecimiento(['nombre' => 'Supermercado Centro', 'tipo' => 'SUPERMERCADO'], 4.5339, -75.6811);

        $this->getJson('/v1/establecimientos')
            ->assertOk()
            ->assertJsonPath('items.0.nombre', 'Supermercado Centro')
            ->assertJsonPath('items.0.tipo', 'SUPERMERCADO')
            ->assertJsonPath('items.0.municipio', 'Armenia')
            ->assertJsonPath('items.0.ubicacion.lat', 4.5339)
            ->assertJsonPath('items.0.ubicacion.lon', -75.6811)
            ->assertJsonPath('pagina.total', 1);
    });

    it('filtra por texto y por municipio', function () {
        crearEstablecimiento(['nombre' => 'Minimercado La Esquina']);
        crearEstablecimiento(['nombre' => 'Tienda El Paisa', 'municipio' => 'Calarcá']);

        $this->getJson('/v1/establecimientos?q=esquina')->assertOk()->assertJsonCount(1, 'items');
        $this->getJson('/v1/establecimientos?municipio=calarca')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.nombre', 'Tienda El Paisa');
    });

    it('con lat/lon ordena por cercanía, informa distanciaKm y respeta el radio', function () {
        // Punto de consulta: parque Bolívar de Armenia (aprox. 4.5339, -75.6811)
        crearEstablecimiento(['nombre' => 'Lejana'], 4.5600, -75.6600);   // ≈ 3.7 km
        crearEstablecimiento(['nombre' => 'Cercana'], 4.5345, -75.6815);  // ≈ 0.08 km
        crearEstablecimiento(['nombre' => 'Fuera de radio'], 4.6500, -75.6000); // > 15 km

        $res = $this->getJson('/v1/establecimientos?lat=4.5339&lon=-75.6811&radioKm=5')->assertOk();

        $res->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.nombre', 'Cercana')
            ->assertJsonPath('items.1.nombre', 'Lejana');
        expect($res->json('items.0.distanciaKm'))->toBeLessThan(0.2)
            ->and($res->json('items.1.distanciaKm'))->toBeGreaterThan(3.0);
    });

    it('rechaza lat sin lon con Problem Details 400', function () {
        $this->getJson('/v1/establecimientos?lat=4.53')
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/problem+json');
    });
})->group('feature');
