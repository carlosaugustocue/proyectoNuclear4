<?php

declare(strict_types=1);

use App\Domains\Canasta\Domain\Exceptions\CanastaInvalidaException;
use App\Domains\Canasta\Domain\Services\ValidadorCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ItemSolicitado;

/** RN-11: una canasta tiene de 1 a 20 productos distintos, cantidades > 0 y unidades comparables (RN-03). */
describe('U-13 ValidadorCanasta', function () {

    $unidades = fn (int $n): array => array_combine(
        array_map(fn (int $i): string => "p{$i}", range(1, $n)),
        array_fill(0, $n, 'kg'),
    );

    it('acepta una canasta válida y normaliza la cantidad a la unidad base', function () {
        $items = ValidadorCanasta::validar(
            [new ItemSolicitado('arroz', '500', 'g'), new ItemSolicitado('leche', '2', 'L')],
            ['arroz' => 'kg', 'leche' => 'L'],
        );

        expect($items)->toHaveCount(2)
            ->and($items[0]->productoId)->toBe('arroz')
            ->and($items[0]->cantidad)->toBe('0.5')
            ->and($items[0]->unidadBase)->toBe('kg')
            ->and($items[1]->cantidad)->toBe('2')
            ->and($items[1]->unidadBase)->toBe('L');
    });

    it('acepta exactamente 20 productos', function () use ($unidades) {
        $items = array_map(fn (int $i) => new ItemSolicitado("p{$i}", '1', 'kg'), range(1, 20));

        expect(ValidadorCanasta::validar($items, $unidades(20)))->toHaveCount(20);
    });

    it('rechaza 21 productos', function () use ($unidades) {
        $items = array_map(fn (int $i) => new ItemSolicitado("p{$i}", '1', 'kg'), range(1, 21));

        ValidadorCanasta::validar($items, $unidades(21));
    })->throws(CanastaInvalidaException::class, '20');

    it('rechaza una canasta vacía', function () {
        ValidadorCanasta::validar([], []);
    })->throws(CanastaInvalidaException::class);

    it('rechaza productos repetidos', function () {
        ValidadorCanasta::validar(
            [new ItemSolicitado('arroz', '1', 'kg'), new ItemSolicitado('arroz', '500', 'g')],
            ['arroz' => 'kg'],
        );
    })->throws(CanastaInvalidaException::class, 'repetido');

    it('rechaza cantidad cero o negativa', function (string $cantidad) {
        ValidadorCanasta::validar([new ItemSolicitado('arroz', $cantidad, 'kg')], ['arroz' => 'kg']);
    })->with(['0', '-1'])->throws(CanastaInvalidaException::class, 'cantidad');

    it('rechaza una unidad desconocida', function () {
        ValidadorCanasta::validar([new ItemSolicitado('arroz', '1', 'libra')], ['arroz' => 'kg']);
    })->throws(CanastaInvalidaException::class, 'unidad');

    it('rechaza una unidad no comparable con la unidad base del producto (RN-03)', function () {
        ValidadorCanasta::validar([new ItemSolicitado('huevos', '1', 'kg')], ['huevos' => 'unidad']);
    })->throws(CanastaInvalidaException::class, 'comparable');

    it('rechaza un producto que no está en el catálogo', function () {
        ValidadorCanasta::validar([new ItemSolicitado('inexistente', '1', 'kg')], []);
    })->throws(CanastaInvalidaException::class, 'catálogo');

    it('reúne todos los errores en la excepción', function () {
        try {
            ValidadorCanasta::validar(
                [new ItemSolicitado('a', '0', 'kg'), new ItemSolicitado('b', '1', 'libra')],
                ['a' => 'kg', 'b' => 'kg'],
            );
            $this->fail('Debía lanzar CanastaInvalidaException');
        } catch (CanastaInvalidaException $e) {
            expect($e->errores)->toHaveCount(2);
        }
    });
});
