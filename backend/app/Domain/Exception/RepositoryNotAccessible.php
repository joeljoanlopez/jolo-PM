<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** El repo no existe o el token no tiene acceso: GitHub responde 404 en ambos casos. */
final class RepositoryNotAccessible extends DomainException
{
    public function __construct(string $what)
    {
        parent::__construct("GitHub no encuentra $what o el token no tiene acceso.");
    }
}
