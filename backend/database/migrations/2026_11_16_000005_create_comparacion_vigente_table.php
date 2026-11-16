<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo de lectura de la comparación (CQRS ligero, ADR-0003): una fila por presentación
 * y establecimiento con el precio publicado, desnormalizada para responder sin JOINs
 * (EC-04). No contiene datos de reportantes (EC-07) ni atributos comerciales (ADR-0009).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE comparacion_vigente (
                presentacion_id           uuid           NOT NULL,
                establecimiento_id        uuid           NOT NULL,
                producto_id               uuid           NOT NULL,
                observacion_id            uuid           NOT NULL,
                precio                    numeric(15,2)  NOT NULL,
                precio_por_unidad         numeric(15,2)  NOT NULL,
                unidad_base               varchar(6)     NOT NULL,
                observado_en              timestamptz    NOT NULL,
                fuente                    varchar(16)    NOT NULL,
                confirmaciones            smallint       NOT NULL DEFAULT 0,
                estado_confianza          varchar(16)    NOT NULL,
                tipo_vigencia             varchar(12)    NOT NULL,
                establecimiento           jsonb          NOT NULL,
                presentacion              jsonb          NOT NULL,
                establecimiento_municipio varchar(60)    NOT NULL,
                ubicacion                 geography(Point,4326) NOT NULL,
                actualizado_en            timestamptz    NOT NULL DEFAULT now(),
                PRIMARY KEY (presentacion_id, establecimiento_id)
            )
        SQL);
        DB::statement('CREATE INDEX comparacion_vigente_producto_idx ON comparacion_vigente (producto_id, precio_por_unidad)');
        DB::statement('CREATE INDEX comparacion_vigente_ubicacion_gist ON comparacion_vigente USING GIST (ubicacion)');
    }

    public function down(): void
    {
        Schema::dropIfExists('comparacion_vigente');
    }
};
