<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** La cuenta ya existe: el asistente de configuración inicial ya no está. */
final class SetupNotAvailable extends DomainException
{
    public function __construct()
    {
        parent::__construct(new Message('auth.setup_not_available'));
    }
}
