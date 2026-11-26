<?php

declare(strict_types=1);

/*
 * Todos los mensajes que la API devuelve al usuario están en español (la PWA los muestra tal cual).
 */
describe('Mensajes de error en español', function () {
    it('traduce los mensajes de validación y usa nombres de campo legibles', function () {
        $r = $this->getJson('/v1/productos?q=a&tamano=99')->assertStatus(400);

        expect($r->json('errors.q.0'))->toBe('La búsqueda debe tener al menos 2 caracteres.')
            ->and($r->json('errors.tamano.0'))->toBe('El tamaño no debe ser mayor que 50.');
    });

    it('traduce los campos obligatorios y el resumen de errores del registro de cuenta', function () {
        $r = $this->postJson('/v1/cuentas', ['correo' => 'x'])->assertStatus(422);

        expect($r->json('errors.contrasena.0'))->toBe('La contraseña es obligatoria.')
            ->and($r->json('detail'))->not->toContain('more error')
            ->and($r->json('detail'))->toContain('(y 3 errores más)');
    });

    it('traduce la ruta inexistente y el método no permitido', function () {
        $this->getJson('/v1/no-existe')->assertNotFound()
            ->assertJsonPath('detail', 'La ruta solicitada no existe.');
        $this->deleteJson('/v1/productos')->assertStatus(405)
            ->assertJsonPath('title', 'Método no permitido')
            ->assertJsonPath('detail', 'Esta operación no está permitida en este recurso.');
    });

    it('ningún mensaje de error de estas solicitudes queda en inglés', function () {
        $respuestas = [
            $this->getJson('/v1/productos?q=a'),
            $this->postJson('/v1/cuentas', []),
            $this->getJson('/v1/no-existe'),
            $this->deleteJson('/v1/productos'),
        ];

        foreach ($respuestas as $r) {
            expect($r->getContent())->not->toMatch('/\b(The|field|must|required|could not|supported|more errors?)\b/');
        }
    });
});
