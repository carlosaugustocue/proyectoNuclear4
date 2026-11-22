<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\Exceptions;

/** La confirmación «sigue igual» no procede (precio propio, superado o invalidado). */
final class ConfirmacionNoAplicableException extends \DomainException {}
