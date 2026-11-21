<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Exceptions;

use DomainException;

/** RN-15: el perfil no puede publicar (no verificado, revocado o establecimiento ajeno). */
final class PublicacionNoAutorizadaException extends DomainException {}
