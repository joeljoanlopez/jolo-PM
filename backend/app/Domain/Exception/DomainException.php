<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/**
 * Error esperado de uso o de GitHub. Lleva el texto para el usuario como
 * Message (sin traducir); getMessage() devuelve solo su clave, para logs.
 */
abstract class DomainException extends \RuntimeException
{
    public function __construct(public readonly Message $reason, ?\Throwable $previous = null)
    {
        parent::__construct($reason->key, 0, $previous);
    }
}
