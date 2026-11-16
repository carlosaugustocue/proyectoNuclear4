<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Volumen SINTÉTICO para la prueba de carga K-01 (EC-04, M-05, M-06).
 *
 * EC-04 fija el entorno: ≥ 2.000 presentaciones y ≥ 300 establecimientos. Esta semilla crea ese
 * volumen directamente en el catálogo, el directorio y el modelo de lectura `comparacion_vigente`,
 * porque K-01 mide la CONSULTA; el flujo de registro se mide aparte (I-02, M-02).
 *
 * ⚠ Productos, establecimientos y precios son inventados (prefijo «[CARGA]»). Nunca deben llegar a una
 * base de datos real: la semilla se niega a correr con APP_ENV=production.
 *
 * Uso: CALIDAD_PRODUCTOS=1000 CALIDAD_ESTABLECIMIENTOS=300 php artisan db:seed --class=VolumenCargaSeeder
 */
final class VolumenCargaSeeder extends Seeder
{
    private const SEMILLA_ALEATORIA = 0.2026;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('VolumenCargaSeeder crea datos sintéticos: no se ejecuta en producción.');
        }

        $productos        = max(1, (int) env('CALIDAD_PRODUCTOS', 1000));          // × 2 presentaciones
        $establecimientos = max(1, (int) env('CALIDAD_ESTABLECIMIENTOS', 300));
        $porPresentacion  = max(1, (int) env('CALIDAD_ESTABLECIMIENTOS_POR_PRESENTACION', 12));

        DB::transaction(function () use ($productos, $establecimientos, $porPresentacion): void {
            DB::select('SELECT setseed(?)', [self::SEMILLA_ALEATORIA]);
            $this->crearCatalogo($productos);
            $this->crearEstablecimientos($establecimientos);
            $this->proyectarPrecios($porPresentacion);
        });
        DB::statement('ANALYZE productos, presentaciones, establecimientos, comparacion_vigente');

        $this->command->info(sprintf(
            'Volumen K-01: %d presentaciones, %d establecimientos, %d filas en comparacion_vigente.',
            DB::table('presentaciones')->count(),
            DB::table('establecimientos')->count(),
            DB::table('comparacion_vigente')->count(),
        ));
    }

    private function crearCatalogo(int $productos): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO productos (id, nombre, marca, categoria, tipo_vigencia, alias, created_at, updated_at)
            SELECT gen_random_uuid(), '[CARGA] Producto ' || g, 'Marca ' || (g % 60),
                   (ARRAY['Granos','Lácteos','Aceites','Bebidas','Frutas y verduras','Aseo'])[1 + g % 6],
                   CASE WHEN g % 3 = 0 THEN 'PERECEDERO' ELSE 'ABARROTE' END, '[]'::jsonb, now(), now()
            FROM generate_series(1, ?) AS g
        SQL, [$productos]);

        // Dos presentaciones por producto: 500 g y 1 kg (comparables por kilogramo, RN-01).
        DB::statement(<<<'SQL'
            INSERT INTO presentaciones (id, producto_id, descripcion, cantidad, unidad, unidades_por_empaque,
                                        unidad_base, created_at, updated_at)
            SELECT gen_random_uuid(), p.id, v.descripcion, v.cantidad, v.unidad, 1, 'KG', now(), now()
            FROM productos p
            CROSS JOIN (VALUES ('Bolsa 500 g', 500, 'G'), ('Bolsa 1 kg', 1, 'KG')) AS v(descripcion, cantidad, unidad)
            WHERE p.nombre LIKE '[CARGA]%'
        SQL);
    }

    private function crearEstablecimientos(int $establecimientos): void
    {
        // Puntos aleatorios dentro del casco urbano aproximado de Armenia.
        DB::statement(<<<'SQL'
            INSERT INTO establecimientos (id, nombre, tipo, direccion, barrio, municipio, estado_directorio,
                                          created_at, updated_at, ubicacion)
            SELECT gen_random_uuid(), '[CARGA] Establecimiento ' || g,
                   (ARRAY['TIENDA_BARRIO','MINIMERCADO','SUPERMERCADO'])[1 + g % 3],
                   'Dirección ' || g, 'Barrio ' || (g % 40), 'Armenia', 'APROBADO', now(), now(),
                   ST_SetSRID(ST_MakePoint(-75.72 + random() * 0.08, 4.50 + random() * 0.07), 4326)::geography
            FROM generate_series(1, ?) AS g
        SQL, [$establecimientos]);
    }

    private function proyectarPrecios(int $porPresentacion): void
    {
        DB::statement(<<<'SQL'
            INSERT INTO comparacion_vigente (
                presentacion_id, establecimiento_id, producto_id, observacion_id, precio, precio_por_unidad,
                unidad_base, observado_en, fuente, confirmaciones, estado_confianza, tipo_vigencia,
                establecimiento, presentacion, establecimiento_municipio, ubicacion, actualizado_en)
            SELECT pr.id, e.id, p.id, gen_random_uuid(),
                   round((b.base * pr.cantidad_kg * (0.9 + random() * 0.2))::numeric, 2),
                   round((b.base * (0.9 + random() * 0.2))::numeric, 2),
                   'KG', now() - random() * interval '6 days', 'REPORTANTE',
                   (random() * 3)::int,
                   CASE WHEN random() < 0.60 THEN 'CONFIABLE'
                        WHEN random() < 0.90 THEN 'NO_CORROBORADO' ELSE 'POR_VERIFICAR' END,
                   p.tipo_vigencia,
                   jsonb_build_object('id', e.id, 'nombre', e.nombre, 'tipo', e.tipo,
                                      'barrio', e.barrio, 'municipio', e.municipio),
                   jsonb_build_object('id', pr.id, 'descripcion', pr.descripcion, 'cantidad', pr.cantidad::text,
                                      'unidad', pr.unidad, 'unidadesPorEmpaque', 1, 'unidadBase', 'KG', 'ean', null),
                   e.municipio, e.ubicacion, now()
            FROM (SELECT id, producto_id, descripcion, cantidad, unidad,
                         CASE unidad WHEN 'G' THEN cantidad / 1000 ELSE cantidad END AS cantidad_kg
                  FROM presentaciones) pr
            JOIN productos p ON p.id = pr.producto_id AND p.nombre LIKE '[CARGA]%'
            JOIN LATERAL (SELECT 2000 + floor(random() * 38000) AS base) b ON true
            JOIN LATERAL (SELECT id, nombre, tipo, barrio, municipio, ubicacion
                          FROM establecimientos
                          WHERE nombre LIKE '[CARGA]%'
                          ORDER BY md5(pr.id::text || id::text)
                          LIMIT ?) e ON true
        SQL, [$porPresentacion]);
    }
}
