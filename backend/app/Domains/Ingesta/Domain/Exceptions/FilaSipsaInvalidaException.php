<?php

declare(strict_types=1);

namespace App\Domains\Ingesta\Domain\Exceptions;

use DomainException;

/** Una fila del anexo SIPSA que la capa anticorrupción no puede traducir; el motivo va en el mensaje. */
final class FilaSipsaInvalidaException extends DomainException {}
