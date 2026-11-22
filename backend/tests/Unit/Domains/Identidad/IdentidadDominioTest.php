<?php

declare(strict_types=1);

use App\Domains\Identidad\Domain\Entities\Cuenta;
use App\Domains\Identidad\Domain\ValueObjects\Correo;
use App\Domains\Identidad\Domain\ValueObjects\EstadoCuenta;
use App\Domains\Identidad\Domain\ValueObjects\PoliticaContrasena;
use App\Domains\Identidad\Domain\ValueObjects\Rol;

/*
 * U-11 Identidad (RF-22, Ley 1581, ADR-0025): correo, política de contraseña y cuenta.
 * El correo NO usa la regla `email` de Laravel (GHSA-5vg9-5847-vvmq, ADR-0012): se valida aquí.
 */

describe('Correo', function () {
    it('normaliza a minúsculas y sin espacios alrededor', function () {
        expect(Correo::de('  Ana.Perez@Ejemplo.TEST ')->valor)->toBe('ana.perez@ejemplo.test');
    });

    it('rechaza correos mal formados', function (string $valor) {
        Correo::de($valor);
    })->throws(InvalidArgumentException::class)->with(['', 'sin-arroba', 'a@', '@b.co', 'a b@c.co', 'a@b', 'a@@b.co']);

    it('rechaza caracteres de control: inyección CRLF (GHSA-5vg9-5847-vvmq)', function (string $valor) {
        Correo::de($valor);
    })->throws(InvalidArgumentException::class)->with(["ana@ejemplo.test\r\nBcc: x@y.z", "ana@ejemplo.test\n", "an\ta@ejemplo.test", "ana@ejemplo.test\0"]);

    it('rechaza correos de más de 120 caracteres', function () {
        Correo::de(str_repeat('a', 110) . '@ejemplo.test');
    })->throws(InvalidArgumentException::class);
});

describe('PoliticaContrasena', function () {
    it('acepta contraseñas de 10 a 128 caracteres', function () {
        expect(PoliticaContrasena::errores('una-frase-larga'))->toBe([])
            ->and(PoliticaContrasena::errores(str_repeat('x', 128) . ''))->toBe([]);
    });

    it('explica por qué rechaza una contraseña', function () {
        expect(PoliticaContrasena::errores('corta'))->toContain('Debe tener al menos 10 caracteres.')
            ->and(PoliticaContrasena::errores(str_repeat('x', 129)))->toContain('Debe tener como máximo 128 caracteres.')
            ->and(PoliticaContrasena::errores('1234567890'))->toContain('No puede ser solo números.');
    });

    it('rechaza que la contraseña contenga el correo', function () {
        expect(PoliticaContrasena::errores('ana@ejemplo.test-2026', Correo::de('ana@ejemplo.test')))
            ->toContain('No puede contener tu correo.');
    });
});

describe('Cuenta', function () {
    it('una cuenta nueva es CIUDADANO y ACTIVA, con su consentimiento registrado', function () {
        $c = Cuenta::registrar('id-1', Correo::de('ana@ejemplo.test'), new DateTimeImmutable('2026-11-22T10:00:00Z'), '2026-11');

        expect($c->rol)->toBe(Rol::CIUDADANO)
            ->and($c->estado)->toBe(EstadoCuenta::ACTIVA)
            ->and($c->versionPolitica)->toBe('2026-11')
            ->and($c->puedeIniciarSesion())->toBeTrue();
    });

    it('una cuenta suspendida no puede iniciar sesión', function () {
        $c = Cuenta::registrar('id-1', Correo::de('ana@ejemplo.test'), new DateTimeImmutable(), '2026-11')->suspender();

        expect($c->estado)->toBe(EstadoCuenta::SUSPENDIDA)->and($c->puedeIniciarSesion())->toBeFalse();
    });

    it('no se puede suspender a un moderador desde la moderación', function () {
        Cuenta::reconstituir('id-2', Correo::de('mod@ejemplo.test'), Rol::MODERADOR, EstadoCuenta::ACTIVA, new DateTimeImmutable(), '2026-11')->suspender();
    })->throws(DomainException::class);

    it('el seudónimo público se deriva del id y no revela el correo (EC-07)', function () {
        $c = Cuenta::registrar('0190a4c2-aaaa-7000-8000-00000000abcd', Correo::de('ana@ejemplo.test'), new DateTimeImmutable(), '2026-11');

        expect($c->seudonimo())->toBe('Usuario ABCD')->not->toContain('ana');
    });
});
