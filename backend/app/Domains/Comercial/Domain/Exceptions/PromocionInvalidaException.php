<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Exceptions;

use DomainException;

final class PromocionInvalidaException extends DomainException
{
    /** @param list<string> $errores */
    public function __construct(public readonly array $errores)
    {
        parent::__construct(implode(' ', $errores));
    }
}
