<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\EstadoConfianza;

final class DetectorAnomalias
{
    private const THRESHOLD_MAD    = 3.5;
    private const THRESHOLD_HIST   = 0.5;   // ±50 % para n < 5
    private const ESCALA_MAD       = 0.6745;

    /**
     * @param list<string> $preciosHistoricos
     */
    public static function evaluar(string $precio, array $preciosHistoricos): EstadoConfianza
    {
        $nuevo   = (float) $precio;
        $valores = array_map('floatval', $preciosHistoricos);
        $n       = count($valores);

        if ($n >= 5) {
            return self::evaluarConMAD($nuevo, $valores);
        }

        return self::evaluarConHistorial($nuevo, $valores);
    }

    /** @param list<float> $valores */
    private static function evaluarConMAD(float $nuevo, array $valores): EstadoConfianza
    {
        $mediana = self::mediana($valores);
        $desv    = array_map(fn ($v) => abs($v - $mediana), $valores);
        $mad     = self::mediana($desv);

        if ($mad == 0.0) {
            return $nuevo == $mediana
                ? EstadoConfianza::CONFIABLE
                : EstadoConfianza::POR_VERIFICAR;
        }

        $score = self::ESCALA_MAD * abs($nuevo - $mediana) / $mad;

        return $score <= self::THRESHOLD_MAD
            ? EstadoConfianza::CONFIABLE
            : EstadoConfianza::POR_VERIFICAR;
    }

    /** @param list<float> $valores */
    private static function evaluarConHistorial(float $nuevo, array $valores): EstadoConfianza
    {
        if (empty($valores)) {
            return EstadoConfianza::CONFIABLE;
        }

        $promedio = array_sum($valores) / count($valores);

        if ($promedio == 0.0) {
            return EstadoConfianza::CONFIABLE;
        }

        $diferencia = abs($nuevo - $promedio) / $promedio;

        return $diferencia <= self::THRESHOLD_HIST
            ? EstadoConfianza::CONFIABLE
            : EstadoConfianza::POR_VERIFICAR;
    }

    /** @param list<float> $valores */
    private static function mediana(array $valores): float
    {
        sort($valores);
        $n = count($valores);
        $mid = (int) floor($n / 2);
        return $n % 2 === 0
            ? ($valores[$mid - 1] + $valores[$mid]) / 2
            : $valores[$mid];
    }
}
