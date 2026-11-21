<?php

declare(strict_types=1);

use App\Domains\Canasta\Application\CompararCanasta;
use App\Domains\Canasta\Domain\Exceptions\CanastaInvalidaException;
use App\Domains\Canasta\Domain\Ports\FuentePreciosVigentes;
use App\Domains\Canasta\Domain\ValueObjects\ItemSolicitado;
use App\Domains\Canasta\Domain\ValueObjects\OfertaProducto;

/** Caso de uso de la canasta (RF-27 a RF-30): valida, calcula y ordena con el criterio declarado. */
describe('CompararCanasta', function () {

    $fuente = fn (array $unidades, array $ofertas): FuentePreciosVigentes => new class ($unidades, $ofertas) implements FuentePreciosVigentes {
        /** @var list<?string> */
        public array $municipios = [];

        public function __construct(private array $unidades, private array $ofertas) {}

        public function unidadesBase(array $productoIds): array
        {
            return array_intersect_key($this->unidades, array_flip($productoIds));
        }

        public function ofertas(array $productoIds, ?string $municipio): array
        {
            $this->municipios[] = $municipio;

            return $this->ofertas;
        }
    };

    it('devuelve establecimientos ordenados, faltantes explícitos y el criterio declarado', function () use ($fuente) {
        $caso = new CompararCanasta($fuente(['arroz' => 'kg', 'leche' => 'L'], [
            new OfertaProducto('arroz', 'e1', 'Tienda Uno', '4000.00'),
            new OfertaProducto('leche', 'e1', 'Tienda Uno', '3000.00'),
            new OfertaProducto('arroz', 'e2', 'Tienda Dos', '3500.00'),
        ]));

        $r = $caso->comparar([new ItemSolicitado('arroz', '1', 'kg'), new ItemSolicitado('leche', '1000', 'mL')], 'disponibilidad', null);

        expect($r['criterioOrden']['campo'])->toBe('disponibilidad')
            ->and($r['criterioOrden']['descripcion'])->toContain('Ningún comercio paga')
            ->and($r['totalProductos'])->toBe(2)
            ->and($r['establecimientos'][0]['establecimiento']['id'])->toBe('e1')
            ->and($r['establecimientos'][0]['total'])->toBe('7000.00')
            ->and($r['establecimientos'][0]['completa'])->toBeTrue()
            ->and($r['establecimientos'][1]['faltantes'])->toBe(['leche'])
            ->and($r['establecimientos'][1]['disponibles'])->toBe(1)
            ->and($r['contenidoPromocional'])->toBe([]);
    });

    it('rechaza la canasta inválida antes de consultar precios', function () use ($fuente) {
        $f = $fuente([], []);
        (new CompararCanasta($f))->comparar([new ItemSolicitado('x', '1', 'kg')], 'total', null);
    })->throws(CanastaInvalidaException::class);

    it('pasa el filtro de municipio a la fuente de precios', function () use ($fuente) {
        $f = $fuente(['arroz' => 'kg'], []);
        (new CompararCanasta($f))->comparar([new ItemSolicitado('arroz', '1', 'kg')], 'total', 'Armenia');

        expect($f->municipios)->toBe(['Armenia']);
    });
});
