<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\Exceptions;

/** La contraseña no cumple la política o la actual no coincide; lleva todos los motivos. */
final class ContrasenaRechazadaException extends \DomainException
{
    /** @param list<string> $motivos */
    public function __construct(public readonly array $motivos)
    {
        parent::__construct(implode(' ', $motivos));
    }
}
