<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\Exceptions;

use RuntimeException;

/** La decisión choca con el estado del caso (ya resuelto, acción que no aplica a su origen…). */
final class DecisionNoAplicableException extends RuntimeException {}
