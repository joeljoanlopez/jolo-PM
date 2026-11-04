<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** El repo no existe o el token no tiene acceso: GitHub responde 404 en ambos casos. */
final class RepositoryNotAccessible extends DomainException
{
    /** @param Message $resource qué se intentaba leer (claves `resource.*`) */
    public function __construct(Message $resource)
    {
        parent::__construct(new Message('repository_not_accessible', ['resource' => $resource]));
    }
}
