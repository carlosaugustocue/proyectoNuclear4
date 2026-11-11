<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

describe('POST /api/v1/precios', function () {

    it('crea un registro de precio y devuelve 201 con el id', function () {
        $response = $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-arroz-500g',
            'establecimiento_id' => 'est-exito-armenia',
            'monto'              => '3250',
            'latitud'            => 4.5339,
            'longitud'           => -75.6811,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id'])
                 ->assertJsonIsObject();

        $id = $response->json('id');

        $this->assertDatabaseHas('registros_precio', [
            'id'                 => $id,
            'producto_id'        => 'prod-arroz-500g',
            'establecimiento_id' => 'est-exito-armenia',
            'monto'              => 3250.00,
            'moneda'             => 'COP',
        ]);
    });

    it('persiste la ubicación geoespacial correctamente', function () {
        $response = $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-leche-1l',
            'establecimiento_id' => 'est-olimpica-armenia',
            'monto'              => '2800',
            'latitud'            => 4.5280,
            'longitud'           => -75.6742,
        ]);

        $response->assertStatus(201);
        $id = $response->json('id');

        $ubicacion = DB::selectOne(
            "SELECT ST_AsText(ubicacion) AS wkt FROM registros_precio WHERE id = ?",
            [$id]
        );

        expect($ubicacion->wkt)->toBe('POINT(-75.6742 4.528)');
    });

    it('acepta moneda explícita distinta de COP', function () {
        $response = $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-cafe-500g',
            'establecimiento_id' => 'est-cafe-origen',
            'monto'              => '12.50',
            'moneda'             => 'USD',
            'latitud'            => 4.5339,
            'longitud'           => -75.6811,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('registros_precio', [
            'id'     => $response->json('id'),
            'moneda' => 'USD',
        ]);
    });

    it('rechaza monto negativo con 422', function () {
        $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-x',
            'establecimiento_id' => 'est-x',
            'monto'              => '-100',
            'latitud'            => 4.5339,
            'longitud'           => -75.6811,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['monto']);
    });

    it('rechaza latitud fuera de rango con 422', function () {
        $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-x',
            'establecimiento_id' => 'est-x',
            'monto'              => '1000',
            'latitud'            => 95.0,
            'longitud'           => -75.6811,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['latitud']);
    });

    it('rechaza campos requeridos faltantes con 422', function () {
        $this->postJson('/api/v1/precios', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors([
                 'producto_id',
                 'establecimiento_id',
                 'monto',
                 'latitud',
                 'longitud',
             ]);
    });

})->group('feature');
