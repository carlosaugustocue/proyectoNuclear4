<?php

declare(strict_types=1);

/*
 * Límites de peticiones por IP del cliente (C-18). Los valores por defecto no afectan a un usuario
 * normal ni a la prueba de humo del pipeline (~410 peticiones en 30 s). El entorno de medición
 * (pruebas-calidad/) los sube para que K-01/K-02, que salen de una sola IP, midan la API y no el límite.
 */
return [
    'limite_lecturas_por_minuto'   => (int) env('LIMITE_LECTURAS_POR_MINUTO', 600),
    'limite_escrituras_por_minuto' => (int) env('LIMITE_ESCRITURAS_POR_MINUTO', 60),
];
