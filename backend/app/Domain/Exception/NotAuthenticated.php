<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** Sin sesión, o con la sesión a medio login (falta el segundo factor o ha caducado). */
final class NotAuthenticated extends DomainException
{
    public static function noSession(): self
    {
        return new self(new Message('auth.unauthenticated'));
    }

    public static function loginExpired(): self
    {
        return new self(new Message('auth.login_expired'));
    }
}
