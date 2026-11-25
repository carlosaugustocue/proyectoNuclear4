<?php

declare(strict_types=1);

/*
 * RF-01 / RF-02 — Catálogo: búsqueda de productos y detalle con presentaciones.
 * Contrato: GET /v1/productos, GET /v1/productos/{productoId} (openapi.yaml).
 */

describe('GET /v1/productos', function () {
    it('busca por nombre sin distinguir tildes ni mayúsculas', function () {
        crearProducto(['nombre' => 'Café molido Águila Roja', 'categoria' => 'Bebidas']);
        crearProducto(['nombre' => 'Arroz blanco Diana']);

        $this->getJson('/v1/productos?q=cafe')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.nombre', 'Café molido Águila Roja')
            ->assertJsonPath('items.0.categoria', 'Bebidas')
            ->assertJsonPath('pagina.total', 1);
    });

    it('busca también por marca y por alias', function () {
        crearProducto(['nombre' => 'Panela redonda', 'marca' => 'La Cabaña', 'alias' => json_encode(['dulce de caña'])]);

        $this->getJson('/v1/productos?q=cabaña')->assertOk()->assertJsonCount(1, 'items');
        $this->getJson('/v1/productos?q=dulce de cana')->assertOk()->assertJsonCount(1, 'items');
    });

    it('busca por EAN de una de sus presentaciones', function () {
        $id = crearProducto();
        crearPresentacion($id, ['ean' => '7702511000014']);
        crearProducto(['nombre' => 'Otro producto']);

        $this->getJson('/v1/productos?ean=7702511000014')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $id);
    });

    it('sin filtros lista el catálogo paginado y ordenado por nombre', function () {
        crearProducto(['nombre' => 'Leche entera']);
        crearProducto(['nombre' => 'Aceite de girasol']);
        crearProducto(['nombre' => 'Huevos AA']);

        $this->getJson('/v1/productos?tamano=2')
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.nombre', 'Aceite de girasol')
            ->assertJsonPath('pagina', ['pagina' => 1, 'tamano' => 2, 'total' => 3]);
    });

    it('rechaza una búsqueda de menos de 2 caracteres con Problem Details 400', function () {
        $this->getJson('/v1/productos?q=a')
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 400);
    });
})->group('feature');

describe('GET /v1/productos/{productoId}', function () {
    it('devuelve el producto con sus presentaciones', function () {
        $id = crearProducto();
        crearPresentacion($id, ['descripcion' => 'Paca 6 x 330 mL', 'cantidad' => '330', 'unidad' => 'ML',
            'unidades_por_empaque' => 6, 'unidad_base' => 'L']);

        $this->getJson("/v1/productos/{$id}")
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('estadoCatalogo', 'APROBADO')
            ->assertJsonPath('presentaciones.0.descripcion', 'Paca 6 x 330 mL')
            ->assertJsonPath('presentaciones.0.cantidad', '330')
            ->assertJsonPath('presentaciones.0.unidad', 'ML')
            ->assertJsonPath('presentaciones.0.unidadesPorEmpaque', 6)
            ->assertJsonPath('presentaciones.0.unidadBase', 'L');
    });

    it('responde 404 en Problem Details si el producto no existe', function () {
        $this->getJson('/v1/productos/0193a1d2-0000-7000-8000-000000000000')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404);
    });
})->group('feature');

describe('Imagen del producto (fotos con licencia libre y crédito visible)', function () {
    it('expone la imagen con su crédito en la búsqueda y en el detalle', function () {
        $id = crearProducto([
            'imagen_url'     => 'https://images.openfoodfacts.org/images/products/770/251/100/0014/front_en.30.400.jpg',
            'imagen_credito' => 'Open Food Facts · CC BY-SA 3.0',
        ]);

        $this->getJson('/v1/productos?q=arroz')
            ->assertOk()
            ->assertJsonPath('items.0.imagen.url', 'https://images.openfoodfacts.org/images/products/770/251/100/0014/front_en.30.400.jpg')
            ->assertJsonPath('items.0.imagen.credito', 'Open Food Facts · CC BY-SA 3.0');
        $this->getJson("/v1/productos/{$id}")->assertOk()->assertJsonPath('imagen.credito', 'Open Food Facts · CC BY-SA 3.0');
    });

    it('sin foto, imagen es null (el cliente muestra la ilustración de la categoría)', function () {
        crearProducto();

        $this->getJson('/v1/productos')->assertOk()->assertJsonPath('items.0.imagen', null);
    });

    it('nunca expone una URL que no sea https (evita javascript: y contenido mixto)', function () {
        crearProducto(['imagen_url' => 'javascript:alert(1)', 'imagen_credito' => 'x']);
        crearProducto(['nombre' => 'Lenteja', 'imagen_url' => 'http://inseguro.example/a.jpg', 'imagen_credito' => 'x']);

        $this->getJson('/v1/productos')->assertOk()
            ->assertJsonPath('items.0.imagen', null)
            ->assertJsonPath('items.1.imagen', null);
    });

    it('expone las fotos empacadas con la PWA como ruta local /productos/ y rechaza cualquier otra ruta', function () {
        crearProducto(['nombre' => 'Lulo', 'imagen_url' => '/productos/lulo.webp', 'imagen_credito' => 'Foto: X, Wikimedia Commons (CC0)']);
        crearProducto(['nombre' => 'Mora', 'imagen_url' => '/productos/../index.html', 'imagen_credito' => 'x']);
        crearProducto(['nombre' => 'Naranja', 'imagen_url' => '//evil.example/productos/a.webp', 'imagen_credito' => 'x']);

        $this->getJson('/v1/productos?q=lulo')->assertOk()
            ->assertJsonPath('items.0.imagen.url', '/productos/lulo.webp')
            ->assertJsonPath('items.0.imagen.credito', 'Foto: X, Wikimedia Commons (CC0)');
        $this->getJson('/v1/productos?q=mora')->assertOk()->assertJsonPath('items.0.imagen', null);
        $this->getJson('/v1/productos?q=naranja')->assertOk()->assertJsonPath('items.0.imagen', null);
    });

    it('la comparación incluye la imagen del producto', function () {
        $id = crearProducto(['imagen_url' => 'https://images.openfoodfacts.org/a.jpg', 'imagen_credito' => 'Open Food Facts · CC BY-SA 3.0']);
        crearPresentacion($id);

        $this->getJson("/v1/comparaciones?productoId={$id}")->assertOk()->assertJsonPath('producto.imagen.url', 'https://images.openfoodfacts.org/a.jpg');
    });
});
