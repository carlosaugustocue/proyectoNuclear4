<?php

declare(strict_types=1);

/*
 * CORS solo para la API pública /v1. En local la PWA usa el proxy de Vite (mismo origen);
 * en Railway la PWA y la API tienen dominios distintos y el de la PWA se declara en
 * CORS_ALLOWED_ORIGINS (lista separada por comas).
 */
return [
    'paths'                    => ['v1/*'],
    'allowed_methods'          => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins'          => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    ))),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['Content-Type', 'Accept', 'Idempotency-Key', 'Authorization'],
    'exposed_headers'          => ['Retry-After'],
    'max_age'                  => 3600,
    'supports_credentials'     => false,
];
