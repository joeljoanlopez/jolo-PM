<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ProjectNotFound extends DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("No existe el proyecto $id.");
    }
}
