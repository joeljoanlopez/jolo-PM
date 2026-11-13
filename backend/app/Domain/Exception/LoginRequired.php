<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Message;

/** No hay sesión, o el paso de la contraseña ha caducado antes del segundo factor. */
final class LoginRequired extends DomainException
{
    public static function unauthenticated(): self
    {
        return new self(new Message('auth.unauthenticated'));
    }

    public static function setupExpired(): self
    {
        return new self(new Message('auth.setup_expired'));
    }

    public static function secondFactorExpired(): self
    {
        return new self(new Message('auth.second_factor_expired'));
    }
}
