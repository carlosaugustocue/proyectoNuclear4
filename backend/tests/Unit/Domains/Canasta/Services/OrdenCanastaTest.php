<?php

declare(strict_types=1);

use App\Domains\Canasta\Domain\Services\OrdenCanasta;
use App\Domains\Canasta\Domain\ValueObjects\LineaCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ResultadoEstablecimiento;

/** RN-13 (ADR-0019): orden por criterios declarados; desempate determinista y neutral por nombre. */
describe('U-12 OrdenCanasta', function () {

    $resultado = function (string $id, string $nombre, string $total, int $disponibles, int $totalProductos = 3): ResultadoEstablecimiento {
        $lineas = array_map(fn (int $i) => new LineaCanasta("p{$i}", '1.00', '1', '1.00'), range(1, $disponibles));
        $faltantes = [];
        for ($i = $disponibles + 1; $i <= $totalProductos; $i++) {
            $faltantes[] = "f{$i}";
        }

        return new ResultadoEstablecimiento($id, $nombre, $total, $lineas, $faltantes, $totalProductos);
    };

    $ids = fn (array $r): array => array_map(fn (ResultadoEstablecimiento $x): string => $x->establecimientoId, $r);

    it('por disponibilidad (defecto): primero quien tiene más productos, aunque su total sea mayor', function () use ($resultado, $ids) {
        $orden = OrdenCanasta::ordenar([
            $resultado('barato-incompleto', 'A', '1000.00', 1),
            $resultado('completo', 'B', '9000.00', 3),
            $resultado('medio', 'C', '5000.00', 2),
        ], OrdenCanasta::DISPONIBILIDAD);

        expect($ids($orden))->toBe(['completo', 'medio', 'barato-incompleto']);
    });

    it('por disponibilidad: con igual disponibilidad, menor total primero', function () use ($resultado, $ids) {
        $orden = OrdenCanasta::ordenar([
            $resultado('caro', 'A', '9000.00', 3),
            $resultado('barato', 'B', '8000.00', 3),
        ], OrdenCanasta::DISPONIBILIDAD);

        expect($ids($orden))->toBe(['barato', 'caro']);
    });

    it('por total: menor total primero; con igual total, más disponibles primero', function () use ($resultado, $ids) {
        $orden = OrdenCanasta::ordenar([
            $resultado('completo', 'A', '9000.00', 3),
            $resultado('incompleto', 'B', '5000.00', 1),
            $resultado('empata-mas', 'C', '5000.00', 2),
        ], OrdenCanasta::TOTAL);

        expect($ids($orden))->toBe(['empata-mas', 'incompleto', 'completo']);
    });

    it('compara totales como decimales, no como texto', function () use ($resultado, $ids) {
        $orden = OrdenCanasta::ordenar([
            $resultado('diezmil', 'A', '10000.00', 3),
            $resultado('nuevemil', 'B', '9000.00', 3),
        ], OrdenCanasta::TOTAL);

        expect($ids($orden))->toBe(['nuevemil', 'diezmil']);
    });

    it('empate total: desempata por nombre, de forma determinista y sin importar el orden de llegada', function () use ($resultado, $ids) {
        $a = $resultado('e-zeta', 'Zeta', '5000.00', 3);
        $b = $resultado('e-alfa', 'Alfa', '5000.00', 3);

        expect($ids(OrdenCanasta::ordenar([$a, $b], OrdenCanasta::DISPONIBILIDAD)))->toBe(['e-alfa', 'e-zeta'])
            ->and($ids(OrdenCanasta::ordenar([$b, $a], OrdenCanasta::DISPONIBILIDAD)))->toBe(['e-alfa', 'e-zeta']);
    });

    it('describe el criterio para mostrarlo al usuario, declarando la neutralidad', function () {
        expect(OrdenCanasta::descripcion(OrdenCanasta::DISPONIBILIDAD))->toContain('más productos')
            ->and(OrdenCanasta::descripcion(OrdenCanasta::TOTAL))->toContain('menor total')
            ->and(OrdenCanasta::descripcion(OrdenCanasta::TOTAL))->toContain('Ningún comercio paga');
    });

    it('rechaza un criterio no declarado', function () {
        OrdenCanasta::ordenar([], 'patrocinado');
    })->throws(InvalidArgumentException::class);
});
