<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class DuplicateProject extends DomainException
{
    public function __construct(string $repository)
    {
        parent::__construct("El repositorio $repository ya está dado de alta.");
    }
}
