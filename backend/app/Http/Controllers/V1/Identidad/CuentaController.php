<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Identidad;

use App\Domains\Identidad\Application\GestionarCuentas;
use App\Domains\Identidad\Domain\Entities\Cuenta;
use App\Domains\Identidad\Domain\Exceptions\ContrasenaRechazadaException;
use App\Domains\Identidad\Domain\Exceptions\CorreoYaRegistradoException;
use App\Domains\Identidad\Domain\ValueObjects\Correo;
use App\Domains\Identidad\Domain\ValueObjects\PoliticaContrasena;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AutenticarCuenta;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** RF-22 (registro con autorización de datos), RF-23 (consultar, actualizar, suprimir), ADR-0025. */
final class CuentaController extends Controller
{
    public function __construct(private readonly GestionarCuentas $cuentas) {}

    /** POST /v1/cuentas */
    public function registrar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            // Sin la regla `email` de Laravel 11 (GHSA-5vg9-5847-vvmq, ADR-0012): la valida el dominio.
            'correo'                 => ['required', 'string', 'max:200', self::reglaCorreo()],
            'contrasena'             => ['required', 'string', self::reglaContrasena((string) $request->input('correo'))],
            'aceptaTratamientoDatos' => ['required', 'accepted'],
            'versionPolitica'        => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        try {
            $registro = $this->cuentas->registrar((string) $datos['correo'], (string) $datos['contrasena'], (string) $datos['versionPolitica']);
        } catch (CorreoYaRegistradoException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return response()->json(self::conSesion($registro), 201)->header('Cache-Control', 'no-store');
    }

    /** GET /v1/cuentas/yo — RF-23: el titular consulta sus datos. */
    public function yo(Request $request): JsonResponse
    {
        $c = AutenticarCuenta::de($request);

        return response()->json([
            ...self::publica($c),
            'correo'         => $c->correo->valor,
            'estado'         => $c->estado->value,
            'consentimiento' => ['fecha' => $c->consentimientoEn->format(DateTimeInterface::ATOM), 'versionPolitica' => $c->versionPolitica],
        ])->header('Cache-Control', 'no-store');
    }

    /** PUT /v1/cuentas/yo/contrasena */
    public function cambiarContrasena(Request $request): Response
    {
        $datos = $request->validate(['actual' => ['required', 'string'], 'nueva' => ['required', 'string']]);

        try {
            $this->cuentas->cambiarContrasena(AutenticarCuenta::de($request), (string) $request->bearerToken(), (string) $datos['actual'], (string) $datos['nueva']);
        } catch (ContrasenaRechazadaException $e) {
            throw ValidationException::withMessages(['nueva' => $e->motivos]);
        }

        return response()->noContent();
    }

    /** DELETE /v1/cuentas/yo — RF-23: supresión; los aportes quedan anonimizados. */
    public function suprimir(Request $request): Response
    {
        $this->cuentas->suprimir(AutenticarCuenta::de($request)->id);

        return response()->noContent();
    }

    /**
     * @param array{cuenta: Cuenta, token: string, expiraEn: DateTimeImmutable} $registro
     * @return array<string, mixed>
     */
    public static function conSesion(array $registro): array
    {
        return [
            'cuenta' => self::publica($registro['cuenta']),
            'sesion' => ['token' => $registro['token'], 'expiraEn' => $registro['expiraEn']->format(DateTimeInterface::ATOM)],
        ];
    }

    /**
     * Lo que se puede mostrar de una cuenta sin datos personales (EC-07).
     *
     * @return array<string, string>
     */
    private static function publica(Cuenta $c): array
    {
        return ['id' => $c->id, 'seudonimo' => $c->seudonimo(), 'rol' => $c->rol->value];
    }

    private static function reglaCorreo(): Closure
    {
        return static function (string $atributo, mixed $valor, Closure $fallar): void {
            try {
                Correo::de((string) $valor);
            } catch (InvalidArgumentException $e) {
                $fallar($e->getMessage());
            }
        };
    }

    private static function reglaContrasena(string $correo): Closure
    {
        return static function (string $atributo, mixed $valor, Closure $fallar) use ($correo): void {
            try {
                $correoVo = Correo::de($correo);
            } catch (InvalidArgumentException) {
                $correoVo = null;
            }
            foreach (PoliticaContrasena::errores((string) $valor, $correoVo) as $motivo) {
                $fallar($motivo);
            }
        };
    }
}
