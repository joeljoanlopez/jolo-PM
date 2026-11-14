<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** Aún no hay cuenta: hay que pasar por la configuración inicial. */
final class SetupRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct(new Message('auth.setup_required'));
    }
}
