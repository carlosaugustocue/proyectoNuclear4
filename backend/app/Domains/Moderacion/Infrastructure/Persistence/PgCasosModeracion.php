<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Infrastructure\Persistence;

use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\AccionModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\CasoModeracion;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class PgCasosModeracion implements CasosModeracion
{
    private const COLUMNAS = ['id', 'observacion_id', 'origen', 'estado', 'detalle', 'creado_en'];

    public function abrir(string $observacionId, string $origen, string $detalle): void
    {
        DB::table('casos_moderacion')->insertOrIgnore([
            'id'             => Uuid::uuid7()->toString(),
            'observacion_id' => $observacionId,
            'origen'         => $origen,
            'estado'         => 'ABIERTO',
            'detalle'        => mb_substr($detalle, 0, 200),
            'creado_en'      => now(),
        ]);
    }

    public function listar(string $estado, int $pagina, int $tamano): array
    {
        $consulta = DB::table('casos_moderacion')->where('estado', $estado);
        $total    = (clone $consulta)->count();
        $filas    = $consulta->orderBy('creado_en')->orderBy('id')
            ->offset(($pagina - 1) * $tamano)->limit($tamano)->get(self::COLUMNAS);

        return ['items' => array_values($filas->map(fn (object $f): CasoModeracion => self::caso($f))->all()), 'total' => $total];
    }

    public function obtener(string $casoId): ?CasoModeracion
    {
        $f = DB::table('casos_moderacion')->where('id', $casoId)->first(self::COLUMNAS);

        return $f === null ? null : self::caso($f);
    }

    public function resolver(string $casoId, AccionModeracion $accion, string $justificacion, string $moderador): void
    {
        DB::transaction(function () use ($casoId, $accion, $justificacion, $moderador): void {
            DB::table('casos_moderacion')->where('id', $casoId)->update(['estado' => 'RESUELTO']);
            DB::table('decisiones_moderacion')->insert([
                'id'            => Uuid::uuid7()->toString(),
                'caso_id'       => $casoId,
                'accion'        => $accion->value,
                'justificacion' => mb_substr($justificacion, 0, 500),
                'moderador'     => $moderador,
                'decidido_en'   => now(),
            ]);
        });
    }

    private static function caso(object $f): CasoModeracion
    {
        /** @var object{id: string, observacion_id: string, origen: string, estado: string, detalle: ?string, creado_en: string} $f */
        return new CasoModeracion(
            id:            (string) $f->id,
            observacionId: (string) $f->observacion_id,
            origen:        (string) $f->origen,
            estado:        (string) $f->estado,
            detalle:       $f->detalle === null ? null : (string) $f->detalle,
            abiertoEn:     (new DateTimeImmutable((string) $f->creado_en))->format(DATE_ATOM),
        );
    }
}
