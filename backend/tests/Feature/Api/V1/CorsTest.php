<?php

declare(strict_types=1);

/* La PWA desplegada en otro dominio puede llamar a /v1 con la cabecera Idempotency-Key. */

it('responde la verificación previa CORS para el origen permitido', function () {
    config(['cors.allowed_origins' => ['https://pwa.ejemplo.test']]);

    $this->call('OPTIONS', '/v1/reportes-precio', server: [
        'HTTP_ORIGIN'                         => 'https://pwa.ejemplo.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD'  => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,idempotency-key',
    ])
        ->assertHeader('Access-Control-Allow-Origin', 'https://pwa.ejemplo.test')
        ->assertHeader('Access-Control-Allow-Headers');
});

it('no autoriza orígenes que no están en la lista', function () {
    config(['cors.allowed_origins' => ['https://pwa.ejemplo.test']]);

    $respuesta = $this->call('OPTIONS', '/v1/productos', server: [
        'HTTP_ORIGIN'                        => 'https://otro.test',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);

    // Con un único origen permitido Laravel siempre devuelve ese origen; el navegador
    // rechaza la respuesta porque no coincide con el suyo.
    expect($respuesta->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://otro.test');
});
